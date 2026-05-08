<?php

namespace Database\Seeders;

use App\Enums\EventType;
use App\Enums\ModuleKey;
use App\Models\Template;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'public_id'            => (string) Str::ulid(),
                'name'                 => 'Toscana',
                'event_type'           => EventType::Wedding->value,
                'default_module_order' => ModuleKey::defaultOrder(),
                'styles'               => [
                    'primary_color' => '#c47c5a',
                    'accent_color'  => '#f9ede0',
                    'font_serif'    => 'Georgia, serif',
                    'font_sans'     => 'Inter, sans-serif',
                    'bg_image_url'  => null,
                ],
            ],
            [
                'public_id'            => (string) Str::ulid(),
                'name'                 => 'Esmeralda',
                'event_type'           => EventType::Quinceanera->value,
                'default_module_order' => ModuleKey::defaultOrder(),
                'styles'               => [
                    'primary_color' => '#6dd5b0',
                    'accent_color'  => '#a8edcf',
                    'font_serif'    => 'Georgia, serif',
                    'font_sans'     => 'Inter, sans-serif',
                    'bg_image_url'  => null,
                ],
            ],
            [
                'public_id'            => (string) Str::ulid(),
                'name'                 => 'Solsticio',
                'event_type'           => EventType::Graduation->value,
                'default_module_order' => ModuleKey::defaultOrder(),
                'styles'               => [
                    'primary_color' => '#f5a623',
                    'accent_color'  => '#fce8c0',
                    'font_serif'    => 'Georgia, serif',
                    'font_sans'     => 'Inter, sans-serif',
                    'bg_image_url'  => null,
                ],
            ],
        ];

        foreach ($templates as $data) {
            Template::firstOrCreate(['name' => $data['name']], $data);
        }
    }
}
