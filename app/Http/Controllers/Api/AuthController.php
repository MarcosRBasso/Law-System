<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
        $this->middleware('auth:sanctum')->except([
            'login', 'register', 'forgotPassword', 'resetPassword'
        ]);
    }

    /**
     * Register a new user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->register($request->validated());
            
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'message' => 'Usuário registrado com sucesso',
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao registrar usuário',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login user
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $credentials = $request->only('email', 'password');
            
            if (!$this->authService->validateCredentials($credentials)) {
                throw ValidationException::withMessages([
                    'email' => ['As credenciais fornecidas são inválidas.'],
                ]);
            }

            $user = User::where('email', $request->email)->first();
            
            // Check if user is active
            if (!$user->is_active) {
                return response()->json([
                    'message' => 'Conta desativada. Entre em contato com o administrador.'
                ], 403);
            }

            // Update last login
            $user->update(['last_login_at' => now()]);

            // Create token with abilities based on user roles
            $abilities = $this->authService->getUserAbilities($user);
            $token = $user->createToken('auth-token', $abilities)->plainTextToken;

            return response()->json([
                'message' => 'Login realizado com sucesso',
                'user' => new UserResource($user->load('roles.permissions')),
                'token' => $token,
                'token_type' => 'Bearer',
                'abilities' => $abilities
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Dados de login inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro interno do servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Revoke current token
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Logout realizado com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao fazer logout',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout from all devices
     */
    public function logoutAll(Request $request): JsonResponse
    {
        try {
            // Revoke all tokens
            $request->user()->tokens()->delete();

            return response()->json([
                'message' => 'Logout de todos os dispositivos realizado com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao fazer logout de todos os dispositivos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get authenticated user profile
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->load('roles.permissions');
            
            return response()->json([
                'user' => new UserResource($user),
                'abilities' => $this->authService->getUserAbilities($user)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao obter perfil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user profile
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->updateProfile(
                $request->user(), 
                $request->validated()
            );

            return response()->json([
                'message' => 'Perfil atualizado com sucesso',
                'user' => new UserResource($user)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao atualizar perfil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $user = $request->user();

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'message' => 'Senha atual incorreta',
                    'errors' => ['current_password' => ['Senha atual incorreta']]
                ], 422);
            }

            $user->update([
                'password' => Hash::make($request->password),
                'password_changed_at' => now()
            ]);

            // Revoke all tokens except current
            $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

            return response()->json([
                'message' => 'Senha alterada com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao alterar senha',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send password reset link
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'message' => 'Link de recuperação enviado para seu email'
                ]);
            }

            return response()->json([
                'message' => 'Não foi possível enviar o link de recuperação',
                'errors' => ['email' => [trans($status)]]
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao enviar link de recuperação',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset password
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->forceFill([
                        'password' => Hash::make($password),
                        'password_changed_at' => now()
                    ])->save();

                    // Revoke all tokens
                    $user->tokens()->delete();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json([
                    'message' => 'Senha redefinida com sucesso'
                ]);
            }

            return response()->json([
                'message' => 'Não foi possível redefinir a senha',
                'errors' => ['email' => [trans($status)]]
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao redefinir senha',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enable two-factor authentication
     */
    public function enableTwoFactor(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $secret = $this->authService->generateTwoFactorSecret($user);

            return response()->json([
                'message' => 'Autenticação de dois fatores habilitada',
                'secret' => $secret,
                'qr_code' => $this->authService->generateQrCode($user, $secret)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao habilitar autenticação de dois fatores',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify two-factor authentication code
     */
    public function verifyTwoFactor(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6'
        ]);

        try {
            $user = $request->user();
            
            if (!$this->authService->verifyTwoFactorCode($user, $request->code)) {
                return response()->json([
                    'message' => 'Código de verificação inválido',
                    'errors' => ['code' => ['Código inválido']]
                ], 422);
            }

            $user->update(['two_factor_confirmed_at' => now()]);

            return response()->json([
                'message' => 'Autenticação de dois fatores confirmada com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao verificar código',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Disable two-factor authentication
     */
    public function disableTwoFactor(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $this->authService->disableTwoFactor($user);

            return response()->json([
                'message' => 'Autenticação de dois fatores desabilitada'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao desabilitar autenticação de dois fatores',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user sessions/tokens
     */
    public function sessions(Request $request): JsonResponse
    {
        try {
            $tokens = $request->user()->tokens()->get()->map(function ($token) {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'abilities' => $token->abilities,
                    'last_used_at' => $token->last_used_at,
                    'created_at' => $token->created_at,
                    'is_current' => $token->id === request()->user()->currentAccessToken()->id
                ];
            });

            return response()->json([
                'sessions' => $tokens
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao obter sessões',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Revoke specific token/session
     */
    public function revokeSession(Request $request, $tokenId): JsonResponse
    {
        try {
            $user = $request->user();
            $token = $user->tokens()->find($tokenId);

            if (!$token) {
                return response()->json([
                    'message' => 'Sessão não encontrada'
                ], 404);
            }

            $token->delete();

            return response()->json([
                'message' => 'Sessão revogada com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao revogar sessão',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}