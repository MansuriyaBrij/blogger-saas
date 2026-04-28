<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'    => ['required', 'string', 'max:255'],
            'content'  => ['required', 'string'],
            'labels'   => ['nullable', 'array'],
            'labels.*' => ['string'],
            'status'   => ['required', 'in:LIVE,DRAFT,SCHEDULED'],
        ];
    }
}
