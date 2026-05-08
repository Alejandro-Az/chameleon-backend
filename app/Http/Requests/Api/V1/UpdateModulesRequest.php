<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ModuleKey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateModulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'modules'              => ['required', 'array', 'min:1'],
            'modules.*.module_key' => ['required', Rule::enum(ModuleKey::class)],
            'modules.*.enabled'    => ['required', 'boolean'],
            'modules.*.order'      => ['required', 'integer', 'min:0'],
        ];
    }
}
