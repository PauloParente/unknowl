<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommunityAvatarUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Verificar se o usuário pode gerenciar a comunidade
        $community = $this->route('community');
        return $community && $community->canBeModeratedBy($this->user());
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
