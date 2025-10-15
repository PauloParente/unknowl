<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'min:1', 'max:5000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ];
    }
}


