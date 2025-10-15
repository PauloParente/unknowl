<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'community_id' => ['required', 'exists:communities,id'],
            'title' => ['required', 'string', 'max:255'],
            'text' => ['nullable', 'string'],
            'media' => ['nullable', 'array', 'max:5'], // Reduzido para 5 arquivos
            'media.*' => ['file', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'], // 2MB por arquivo
        ];
    }

    public function messages(): array
    {
        return [
            'media.max' => 'Você pode enviar no máximo 5 imagens por post.',
            'media.*.max' => 'Cada imagem deve ter no máximo 2MB.',
            'media.*.mimes' => 'Apenas arquivos JPG, PNG, GIF e WEBP são permitidos.',
        ];
    }
}


