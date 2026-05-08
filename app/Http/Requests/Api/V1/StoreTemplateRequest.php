<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\EventType;
use App\Enums\ModuleKey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                          => ['required', 'string', 'max:120'],
            'event_type'                    => ['required', Rule::enum(EventType::class)],
            'default_module_order'          => ['required', 'array', 'min:1'],
            'default_module_order.*'        => ['string', Rule::enum(ModuleKey::class)],
            'styles'                        => ['required', 'array'],
            'styles.primary_color'          => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'styles.accent_color'           => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'styles.font_serif'             => ['required', 'string', 'max:120'],
            'styles.font_sans'              => ['required', 'string', 'max:120'],
            'styles.bg_image_url'           => ['nullable', 'url', 'max:500'],
        ];
    }
}
