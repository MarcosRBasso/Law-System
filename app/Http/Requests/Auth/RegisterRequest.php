<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'oab_number' => ['nullable', 'string', 'max:20', 'unique:users'],
            'oab_state' => ['nullable', 'string', 'size:2', 'required_with:oab_number'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['nullable', 'string', 'exists:roles,name'],
            'specializations' => ['nullable', 'array'],
            'specializations.*' => ['string', 'max:100'],
            'terms_accepted' => ['required', 'accepted'],
            'privacy_accepted' => ['required', 'accepted']
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O campo nome é obrigatório.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'email.required' => 'O campo email é obrigatório.',
            'email.email' => 'O email deve ter um formato válido.',
            'email.unique' => 'Este email já está em uso.',
            'password.required' => 'O campo senha é obrigatório.',
            'password.confirmed' => 'A confirmação da senha não confere.',
            'oab_number.unique' => 'Este número da OAB já está cadastrado.',
            'oab_state.required_with' => 'O estado da OAB é obrigatório quando o número da OAB é informado.',
            'oab_state.size' => 'O estado da OAB deve ter 2 caracteres.',
            'role.exists' => 'O perfil selecionado não existe.',
            'terms_accepted.required' => 'Você deve aceitar os termos de uso.',
            'terms_accepted.accepted' => 'Você deve aceitar os termos de uso.',
            'privacy_accepted.required' => 'Você deve aceitar a política de privacidade.',
            'privacy_accepted.accepted' => 'Você deve aceitar a política de privacidade.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'email' => 'email',
            'password' => 'senha',
            'oab_number' => 'número da OAB',
            'oab_state' => 'estado da OAB',
            'phone' => 'telefone',
            'role' => 'perfil',
            'specializations' => 'especializações',
            'terms_accepted' => 'termos de uso',
            'privacy_accepted' => 'política de privacidade'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate OAB number format if provided
            if ($this->oab_number) {
                if (!$this->isValidOabNumber($this->oab_number)) {
                    $validator->errors()->add('oab_number', 'O número da OAB deve ter um formato válido.');
                }
            }

            // Validate OAB state if provided
            if ($this->oab_state) {
                if (!$this->isValidOabState($this->oab_state)) {
                    $validator->errors()->add('oab_state', 'O estado da OAB deve ser um estado brasileiro válido.');
                }
            }

            // Validate role permissions
            if ($this->role) {
                $role = Role::where('name', $this->role)->first();
                if ($role && !$this->canAssignRole($role)) {
                    $validator->errors()->add('role', 'Você não tem permissão para atribuir este perfil.');
                }
            }
        });
    }

    /**
     * Validate OAB number format
     */
    protected function isValidOabNumber(string $oabNumber): bool
    {
        // Remove non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $oabNumber);
        
        // OAB numbers should have between 4 and 6 digits
        return strlen($cleaned) >= 4 && strlen($cleaned) <= 6;
    }

    /**
     * Validate OAB state code
     */
    protected function isValidOabState(string $state): bool
    {
        $validStates = [
            'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 
            'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 
            'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
        ];

        return in_array(strtoupper($state), $validStates);
    }

    /**
     * Check if current user can assign the given role
     */
    protected function canAssignRole(Role $role): bool
    {
        // If no authenticated user (public registration), only allow basic roles
        if (!auth()->check()) {
            $allowedRoles = ['advogado', 'cliente'];
            return in_array($role->name, $allowedRoles);
        }

        // Check if authenticated user has permission to assign roles
        return auth()->user()->can('users.assign-roles');
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize OAB state to uppercase
        if ($this->oab_state) {
            $this->merge([
                'oab_state' => strtoupper($this->oab_state)
            ]);
        }

        // Set default role if not provided
        if (!$this->role) {
            $this->merge([
                'role' => 'advogado'
            ]);
        }

        // Clean phone number
        if ($this->phone) {
            $cleanPhone = preg_replace('/[^0-9]/', '', $this->phone);
            $this->merge([
                'phone' => $cleanPhone
            ]);
        }
    }
}