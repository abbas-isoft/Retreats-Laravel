<?php

namespace App\Services;

use App\Models\PropertySeoSetting;
use Illuminate\Database\Eloquent\Model;

class SeoSettingsService
{
    public function update(Model $owner, array $values): PropertySeoSetting
    {
        return $owner->seoSetting()->updateOrCreate([], $values);
    }
}
