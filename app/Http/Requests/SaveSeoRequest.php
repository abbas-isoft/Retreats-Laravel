<?php

namespace App\Http\Requests;

use App\Support\SeoPayload;
use Illuminate\Foundation\Http\FormRequest;

class SaveSeoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->replace(SeoPayload::sanitize($this->all()));
    }

    public function rules(): array
    {
        return SeoPayload::rules();
    }
}
