<?php

namespace App\Services;

use App\Enums\ModuleKey;
use App\Models\Event;
use App\Models\Template;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventService
{
    public function generateSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 1;

        while (Event::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public function create(User $owner, array $data): Event
    {
        $data['slug']     = $this->generateSlug($data['name']);
        $data['owner_id'] = $owner->id;
        $data['status']   = 'draft';

        $event = Event::create($data);

        $this->initializeModules($event, $data['template_id'] ?? null);

        return $event;
    }

    public function initializeModules(Event $event, ?int $templateId): void
    {
        $order = ModuleKey::defaultOrder();

        if ($templateId) {
            $template = Template::find($templateId);
            if ($template && is_array($template->default_module_order)) {
                $order = $template->default_module_order;
            }
        }

        $configs = [];
        foreach ($order as $position => $moduleKey) {
            $configs[] = [
                'event_id'   => $event->id,
                'module_key' => $moduleKey,
                'enabled'    => true,
                'order'      => $position,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $event->moduleConfigs()->insert($configs);
    }

    public function updateModules(Event $event, array $modules): void
    {
        DB::transaction(function () use ($event, $modules) {
            foreach ($modules as $item) {
                $event->moduleConfigs()
                    ->where('module_key', $item['module_key'])
                    ->update([
                        'enabled' => $item['enabled'],
                        'order'   => $item['order'],
                    ]);
            }
        });
    }

    public function isOwner(User $user, Event $event): bool
    {
        return $event->owner_id === $user->id;
    }
}
