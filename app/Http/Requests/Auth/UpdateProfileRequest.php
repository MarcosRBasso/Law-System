<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
        $userId = $this->user()->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes', 
                'required', 
                'string', 
                'email', 
                'max:255', 
                Rule::unique('users')->ignore($userId)
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'oab_number' => [
                'nullable', 
                'string', 
                'max:20', 
                Rule::unique('users')->ignore($userId)
            ],
            'oab_state' => ['nullable', 'string', 'size:2', 'required_with:oab_number'],
            'specializations' => ['nullable', 'array'],
            'specializations.*' => ['string', 'max:100'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'size:2'],
            'zip_code' => ['nullable', 'string', 'max:10'],
            'notification_preferences' => ['nullable', 'array'],
            'notification_preferences.email' => ['boolean'],
            'notification_preferences.sms' => ['boolean'],
            'notification_preferences.push' => ['boolean'],
            'notification_preferences.deadlines' => ['boolean'],
            'notification_preferences.payments' => ['boolean'],
            'notification_preferences.movements' => ['boolean']
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
            'phone.max' => 'O telefone não pode ter mais de 20 caracteres.',
            'oab_number.unique' => 'Este número da OAB já está cadastrado.',
            'oab_state.required_with' => 'O estado da OAB é obrigatório quando o número da OAB é informado.',
            'oab_state.size' => 'O estado da OAB deve ter 2 caracteres.',
            'avatar.image' => 'O arquivo deve ser uma imagem.',
            'avatar.mimes' => 'A imagem deve ser do tipo: jpeg, png, jpg ou gif.',
            'avatar.max' => 'A imagem não pode ter mais de 2MB.',
            'bio.max' => 'A biografia não pode ter mais de 1000 caracteres.',
            'linkedin_url.url' => 'A URL do LinkedIn deve ser válida.',
            'website_url.url' => 'A URL do website deve ser válida.',
            'address.max' => 'O endereço não pode ter mais de 500 caracteres.',
            'city.max' => 'A cidade não pode ter mais de 100 caracteres.',
            'state.size' => 'O estado deve ter 2 caracteres.',
            'zip_code.max' => 'O CEP não pode ter mais de 10 caracteres.'
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
            'phone' => 'telefone',
            'oab_number' => 'número da OAB',
            'oab_state' => 'estado da OAB',
            'specializations' => 'especializações',
            'avatar' => 'avatar',
            'bio' => 'biografia',
            'linkedin_url' => 'URL do LinkedIn',
            'website_url' => 'URL do website',
            'address' => 'endereço',
            'city' => 'cidade',
            'state' => 'estado',
            'zip_code' => 'CEP'
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

            // Validate phone number format if provided
            if ($this->phone) {
                if (!$this->isValidPhoneNumber($this->phone)) {
                    $validator->errors()->add('phone', 'O telefone deve ter um formato válido.');
                }
            }

            // Validate zip code format if provided
            if ($this->zip_code) {
                if (!$this->isValidZipCode($this->zip_code)) {
                    $validator->errors()->add('zip_code', 'O CEP deve ter um formato válido.');
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
     * Validate phone number format
     */
    protected function isValidPhoneNumber(string $phone): bool
    {
        // Remove non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        
        // Brazilian phone numbers should have 10 or 11 digits
        return strlen($cleaned) >= 10 && strlen($cleaned) <= 11;
    }

    /**
     * Validate zip code format
     */
    protected function isValidZipCode(string $zipCode): bool
    {
        // Remove non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $zipCode);
        
        // Brazilian zip codes should have 8 digits
        return strlen($cleaned) === 8;
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

        // Normalize state to uppercase
        if ($this->state) {
            $this->merge([
                'state' => strtoupper($this->state)
            ]);
        }

        // Clean phone number
        if ($this->phone) {
            $cleanPhone = preg_replace('/[^0-9]/', '', $this->phone);
            $this->merge([
                'phone' => $cleanPhone
            ]);
        }

        // Clean zip code
        if ($this->zip_code) {
            $cleanZipCode = preg_replace('/[^0-9]/', '', $this->zip_code);
            $this->merge([
                'zip_code' => $cleanZipCode
            ]);
        }
    }
}