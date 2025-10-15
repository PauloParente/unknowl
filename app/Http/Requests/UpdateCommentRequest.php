<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->id === $this->route('comment')->user_id;
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'min:1', 'max:5000'],
        ];
    }
}
