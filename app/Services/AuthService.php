<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AuthService
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Register a new user
     */
    public function register(array $data): User
    {
        // Create user
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'oab_number' => $data['oab_number'] ?? null,
            'oab_state' => $data['oab_state'] ?? null,
            'phone' => $data['phone'] ?? null,
            'is_active' => true,
            'email_verified_at' => now(), // Auto-verify for now
        ]);

        // Assign default role
        $defaultRole = $data['role'] ?? 'advogado';
        if (Role::where('name', $defaultRole)->exists()) {
            $user->assignRole($defaultRole);
        }

        // Log activity
        activity('user_registered')
            ->performedOn($user)
            ->causedBy($user)
            ->log('Usuário registrado no sistema');

        return $user->load('roles.permissions');
    }

    /**
     * Validate user credentials
     */
    public function validateCredentials(array $credentials): bool
    {
        return Auth::attempt($credentials);
    }

    /**
     * Get user abilities based on roles and permissions
     */
    public function getUserAbilities(User $user): array
    {
        $abilities = [];

        // Get all permissions from user roles
        $permissions = $user->getAllPermissions();

        foreach ($permissions as $permission) {
            $abilities[] = $permission->name;
        }

        // Add role-based abilities
        foreach ($user->roles as $role) {
            $abilities[] = "role:{$role->name}";
        }

        // Add user-specific abilities
        $abilities[] = "user:{$user->id}";

        return array_unique($abilities);
    }

    /**
     * Update user profile
     */
    public function updateProfile(User $user, array $data): User
    {
        $updateData = [];

        // Only update provided fields
        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }

        if (isset($data['phone'])) {
            $updateData['phone'] = $data['phone'];
        }

        if (isset($data['oab_number'])) {
            $updateData['oab_number'] = $data['oab_number'];
        }

        if (isset($data['oab_state'])) {
            $updateData['oab_state'] = $data['oab_state'];
        }

        if (isset($data['specializations'])) {
            $updateData['specializations'] = $data['specializations'];
        }

        if (isset($data['avatar'])) {
            // Handle avatar upload
            $updateData['avatar'] = $this->handleAvatarUpload($data['avatar']);
        }

        $user->update($updateData);

        // Log activity
        activity('profile_updated')
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties(['updated_fields' => array_keys($updateData)])
            ->log('Perfil atualizado');

        return $user->fresh('roles.permissions');
    }

    /**
     * Generate two-factor authentication secret
     */
    public function generateTwoFactorSecret(User $user): string
    {
        $secret = $this->google2fa->generateSecretKey();
        
        $user->update([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode($this->generateRecoveryCodes()))
        ]);

        return $secret;
    }

    /**
     * Generate QR code for two-factor authentication
     */
    public function generateQrCode(User $user, string $secret): string
    {
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        return QrCode::format('svg')->size(200)->generate($qrCodeUrl);
    }

    /**
     * Verify two-factor authentication code
     */
    public function verifyTwoFactorCode(User $user, string $code): bool
    {
        if (!$user->two_factor_secret) {
            return false;
        }

        $secret = decrypt($user->two_factor_secret);
        
        return $this->google2fa->verifyKey($secret, $code);
    }

    /**
     * Disable two-factor authentication
     */
    public function disableTwoFactor(User $user): void
    {
        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null
        ]);

        // Log activity
        activity('two_factor_disabled')
            ->performedOn($user)
            ->causedBy($user)
            ->log('Autenticação de dois fatores desabilitada');
    }

    /**
     * Generate recovery codes for two-factor authentication
     */
    protected function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = Str::random(10);
        }
        return $codes;
    }

    /**
     * Handle avatar upload
     */
    protected function handleAvatarUpload($avatar): ?string
    {
        if (!$avatar) {
            return null;
        }

        // Store avatar in storage/app/public/avatars
        $path = $avatar->store('avatars', 'public');
        
        return $path;
    }

    /**
     * Check if user has specific permission
     */
    public function userHasPermission(User $user, string $permission): bool
    {
        return $user->can($permission);
    }

    /**
     * Check if user has specific role
     */
    public function userHasRole(User $user, string $role): bool
    {
        return $user->hasRole($role);
    }

    /**
     * Get user permissions grouped by module
     */
    public function getUserPermissionsByModule(User $user): array
    {
        $permissions = $user->getAllPermissions();
        $grouped = [];

        foreach ($permissions as $permission) {
            $parts = explode('.', $permission->name);
            $module = $parts[0] ?? 'general';
            
            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }
            
            $grouped[$module][] = $permission->name;
        }

        return $grouped;
    }

    /**
     * Validate password strength
     */
    public function validatePasswordStrength(string $password): array
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'A senha deve ter pelo menos 8 caracteres';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'A senha deve conter pelo menos uma letra maiúscula';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'A senha deve conter pelo menos uma letra minúscula';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'A senha deve conter pelo menos um número';
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'A senha deve conter pelo menos um caractere especial';
        }

        return $errors;
    }

    /**
     * Check if password needs to be changed
     */
    public function passwordNeedsChange(User $user): bool
    {
        if (!$user->password_changed_at) {
            return true;
        }

        $maxAge = config('juridico.security.password_max_age', 90); // days
        return $user->password_changed_at->diffInDays(now()) > $maxAge;
    }

    /**
     * Get user login history
     */
    public function getUserLoginHistory(User $user, int $limit = 10): array
    {
        return activity('user_login')
            ->causedBy($user)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($activity) {
                return [
                    'ip_address' => $activity->properties['ip_address'] ?? null,
                    'user_agent' => $activity->properties['user_agent'] ?? null,
                    'location' => $activity->properties['location'] ?? null,
                    'created_at' => $activity->created_at
                ];
            })
            ->toArray();
    }

    /**
     * Log user login activity
     */
    public function logLoginActivity(User $user, array $data = []): void
    {
        activity('user_login')
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties([
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'location' => $data['location'] ?? null
            ])
            ->log('Usuário fez login no sistema');
    }

    /**
     * Check for suspicious login activity
     */
    public function checkSuspiciousActivity(User $user): bool
    {
        $recentLogins = activity('user_login')
            ->causedBy($user)
            ->where('created_at', '>=', now()->subHours(1))
            ->count();

        // Flag if more than 5 login attempts in the last hour
        return $recentLogins > 5;
    }

    /**
     * Lock user account
     */
    public function lockAccount(User $user, string $reason = null): void
    {
        $user->update([
            'is_active' => false,
            'locked_at' => now(),
            'lock_reason' => $reason
        ]);

        // Revoke all tokens
        $user->tokens()->delete();

        // Log activity
        activity('account_locked')
            ->performedOn($user)
            ->withProperties(['reason' => $reason])
            ->log('Conta bloqueada');
    }

    /**
     * Unlock user account
     */
    public function unlockAccount(User $user): void
    {
        $user->update([
            'is_active' => true,
            'locked_at' => null,
            'lock_reason' => null
        ]);

        // Log activity
        activity('account_unlocked')
            ->performedOn($user)
            ->log('Conta desbloqueada');
    }
}