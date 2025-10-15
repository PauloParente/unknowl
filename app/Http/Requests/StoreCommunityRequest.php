<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommunityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:21',
                'regex:/^[a-z0-9_-]+$/',
                'unique:communities,name',
            ],
            'title' => [
                'required',
                'string',
                'max:100',
            ],
            'description' => [
                'nullable',
                'string',
                'max:500',
            ],
            'rules' => [
                'nullable',
                'array',
                'max:10',
            ],
            'rules.*' => [
                'string',
                'max:200',
            ],
            'avatar' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,gif,svg',
                'max:2048',
            ],
            'cover' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,gif,svg',
                'max:5120',
            ],
            'is_public' => [
                'boolean',
            ],
            'requires_approval' => [
                'boolean',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome da comunidade é obrigatório.',
            'name.min' => 'O nome da comunidade deve ter pelo menos 3 caracteres.',
            'name.max' => 'O nome da comunidade deve ter no máximo 21 caracteres.',
            'name.regex' => 'O nome da comunidade pode conter apenas letras minúsculas, números, underscore e hífen.',
            'name.unique' => 'Este nome de comunidade já existe.',
            'title.required' => 'O título da comunidade é obrigatório.',
            'title.max' => 'O título da comunidade deve ter no máximo 100 caracteres.',
            'description.max' => 'A descrição deve ter no máximo 500 caracteres.',
            'rules.max' => 'Você pode adicionar no máximo 10 regras.',
            'rules.*.max' => 'Cada regra deve ter no máximo 200 caracteres.',
            'avatar.image' => 'O avatar deve ser uma imagem válida.',
            'avatar.mimes' => 'O avatar deve ser um arquivo do tipo: jpeg, png, jpg, gif, svg.',
            'avatar.max' => 'O avatar deve ter no máximo 2MB.',
            'cover.image' => 'A imagem de capa deve ser uma imagem válida.',
            'cover.mimes' => 'A imagem de capa deve ser um arquivo do tipo: jpeg, png, jpg, gif, svg.',
            'cover.max' => 'A imagem de capa deve ter no máximo 5MB.',
        ];
    }
}
