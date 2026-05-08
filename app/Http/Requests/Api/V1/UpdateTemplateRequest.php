<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\EventType;
use App\Enums\ModuleKey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                   => ['sometimes', 'string', 'max:120'],
            'event_type'             => ['sometimes', Rule::enum(EventType::class)],
            'default_module_order'   => ['sometimes', 'array', 'min:1'],
            'default_module_order.*' => ['string', Rule::enum(ModuleKey::class)],
            'styles'                 => ['sometimes', 'array'],
            'styles.primary_color'   => ['sometimes', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'styles.accent_color'    => ['sometimes', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'styles.font_serif'      => ['sometimes', 'string', 'max:120'],
            'styles.font_sans'       => ['sometimes', 'string', 'max:120'],
            'styles.bg_image_url'    => ['nullable', 'url', 'max:500'],
        ];
    }
}
