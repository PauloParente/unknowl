<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class AvatarUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // O usuário pode alterar apenas seu próprio avatar
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'avatar' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg,gif,webp',
                'max:2048', // 2MB máximo
                'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'avatar.required' => 'Uma imagem é obrigatória.',
            'avatar.image' => 'O arquivo deve ser uma imagem válida.',
            'avatar.mimes' => 'A imagem deve ser dos tipos: jpeg, png, jpg, gif ou webp.',
            'avatar.max' => 'A imagem não pode ser maior que 2MB.',
            'avatar.dimensions' => 'A imagem deve ter entre 100x100 e 2000x2000 pixels.',
        ];
    }
}
