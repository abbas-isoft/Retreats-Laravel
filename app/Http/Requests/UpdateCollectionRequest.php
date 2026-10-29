<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesSeoPayload;
use App\Support\SeoPayload;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCollectionRequest extends FormRequest
{
    use AuthorizesSeoPayload;

    protected function prepareForValidation(): void
    {
        if (is_array($this->input('seo'))) {
            $this->merge(['seo' => SeoPayload::sanitize($this->input('seo'))]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'seo' => ['sometimes', 'nullable', 'array'],
            ...SeoPayload::rules('seo.'),
        ];
    }
}
