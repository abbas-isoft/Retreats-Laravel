<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Gate;

trait AuthorizesSeoPayload
{
    public function authorize(): bool
    {
        if (! $this->exists('seo')) {
            return true;
        }

        return $this->user() !== null
            && Gate::forUser($this->user())->allows('manage-content-seo');
    }

    protected function failedAuthorization(): void
    {
        if ($this->user() === null) {
            throw new AuthenticationException;
        }

        throw new AuthorizationException;
    }
}
