<?php

namespace App\Http\Requests;

use App\Models\Community;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCommunityCoverRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $community = $this->route('community');
        return $this->user()->can('updateSettings', $community);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cover' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png,gif,webp',
                'max:5120', // 5MB em KB
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'cover.required' => 'É necessário selecionar uma imagem para a capa.',
            'cover.file' => 'O arquivo deve ser válido.',
            'cover.image' => 'O arquivo deve ser uma imagem.',
            'cover.mimes' => 'A imagem deve ser do tipo: jpeg, jpg, png, gif ou webp.',
            'cover.max' => 'A imagem não pode ser maior que 5MB.',
        ];
    }
}
