<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesSeoPayload;
use App\Support\SeoPayload;
use Illuminate\Foundation\Http\FormRequest;

class SaveRegionRequest extends FormRequest
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
        $nameRules = ['required', 'string', 'max:255'];
        if (! $this->isMethod('post')) {
            $nameRules[] = 'unique:regions,name,'.$this->route('id');
        }

        return [
            'name' => $nameRules,
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'property_ids' => [$this->isMethod('post') ? 'required' : 'nullable', 'array'],
            'property_ids.*' => ['exists:property_contents,property_id'],
            'seo' => ['sometimes', 'nullable', 'array'],
            ...SeoPayload::rules('seo.'),
        ];
    }
}
