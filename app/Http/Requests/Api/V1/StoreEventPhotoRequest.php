<?php

namespace App\Http\Requests\Api\V1;

use App\Models\EventPhoto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\In;

class StoreEventPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller via service
    }

    public function rules(): array
    {
        return [
            'photo'         => ['required', 'image', 'max:4096'],
            'type'          => ['nullable', new In([
                EventPhoto::TYPE_GALLERY,
                EventPhoto::TYPE_HERO,
                EventPhoto::TYPE_DRESS_CODE,
                EventPhoto::TYPE_STORY,
            ])],
            'caption'       => ['nullable', 'string', 'max:255'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
