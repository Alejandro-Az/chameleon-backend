# Plan 1 — Backend Foundation (Events Domain)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Crear el dominio Events en kaan-core-backend: migraciones, modelos, recursos, controllers y rutas para eventos, templates y configuración de módulos.

**Architecture:** Tres tablas nuevas (`templates`, `events`, `event_module_configs`) con sus modelos Eloquent, API Resources y controllers siguiendo el patrón `Controller → Form Request → Service → API Resource`. Las rutas públicas del evento no requieren JWT; las del master usan el middleware estándar del proyecto.

**Tech Stack:** Laravel 12 · PHP 8.2 · tymon/jwt-auth · spatie/laravel-permission · PHPUnit 11

---

## Archivos a crear/modificar

| Acción | Archivo |
|---|---|
| Crear | `app/Enums/EventType.php` |
| Crear | `app/Enums/ModuleKey.php` |
| Modificar | `config/kaan.php` |
| Crear | `database/migrations/2026_04_28_100001_create_templates_table.php` |
| Crear | `database/migrations/2026_04_28_100002_create_events_table.php` |
| Crear | `database/migrations/2026_04_28_100003_create_event_module_configs_table.php` |
| Crear | `app/Models/Template.php` |
| Crear | `app/Models/Event.php` |
| Crear | `app/Models/EventModuleConfig.php` |
| Crear | `database/factories/TemplateFactory.php` |
| Crear | `database/factories/EventFactory.php` |
| Crear | `app/Http/Resources/TemplateResource.php` |
| Crear | `app/Http/Resources/EventResource.php` |
| Crear | `app/Http/Resources/EventModuleConfigResource.php` |
| Crear | `app/Http/Requests/Api/V1/StoreTemplateRequest.php` |
| Crear | `app/Http/Requests/Api/V1/StoreEventRequest.php` |
| Crear | `app/Http/Requests/Api/V1/UpdateEventRequest.php` |
| Crear | `app/Http/Requests/Api/V1/UpdateModulesRequest.php` |
| Crear | `app/Services/EventService.php` |
| Crear | `app/Http/Controllers/Api/V1/TemplateController.php` |
| Crear | `app/Http/Controllers/Api/V1/EventController.php` |
| Crear | `routes/api/v1/events.php` |
| Crear | `routes/api/v1/admin.templates.php` |
| Modificar | `routes/api.php` |
| Crear | `database/seeders/TemplatesSeeder.php` |
| Modificar | `database/seeders/DatabaseSeeder.php` |
| Crear | `tests/Feature/Events/TemplatesTest.php` |
| Crear | `tests/Feature/Events/EventCrudTest.php` |
| Crear | `tests/Feature/Events/EventModulesTest.php` |

---

## Task 1: Enums y feature flag

**Files:**
- Create: `app/Enums/EventType.php`
- Create: `app/Enums/ModuleKey.php`
- Modify: `config/kaan.php`

- [ ] **Step 1: Crear EventType enum**

```php
<?php
// app/Enums/EventType.php
namespace App\Enums;

enum EventType: string
{
    case Wedding      = 'wedding';
    case Quinceanera  = 'quinceanera';
    case Graduation   = 'graduation';
    case Birthday     = 'birthday';
    case Party        = 'party';
    case Other        = 'other';
}
```

- [ ] **Step 2: Crear ModuleKey enum**

```php
<?php
// app/Enums/ModuleKey.php
namespace App\Enums;

enum ModuleKey: string
{
    case Rsvp            = 'rsvp';
    case Gifts           = 'gifts';
    case Songs           = 'songs';
    case Schedule        = 'schedule';
    case Story           = 'story';
    case DressCode       = 'dress_code';
    case Gallery         = 'gallery';
    case RomanticPhrases = 'romantic_phrases';
    case Attendance      = 'attendance';
    case Location        = 'location';

    public static function defaultOrder(): array
    {
        return [
            self::Rsvp->value,
            self::Location->value,
            self::Schedule->value,
            self::Story->value,
            self::Gifts->value,
            self::Songs->value,
            self::DressCode->value,
            self::RomanticPhrases->value,
            self::Gallery->value,
            self::Attendance->value,
        ];
    }
}
```

- [ ] **Step 3: Añadir feature flag en config/kaan.php**

Localizar el array `features` en `config/kaan.php` y añadir:

```php
'events' => env('KAAN_FEATURE_EVENTS', true),
```

- [ ] **Step 4: Commit**

```bash
git add app/Enums/EventType.php app/Enums/ModuleKey.php config/kaan.php
git commit -m "feat(events): enums EventType y ModuleKey, feature flag"
```

---

## Task 2: Migraciones

**Files:**
- Create: `database/migrations/2026_04_28_100001_create_templates_table.php`
- Create: `database/migrations/2026_04_28_100002_create_events_table.php`
- Create: `database/migrations/2026_04_28_100003_create_event_module_configs_table.php`

- [ ] **Step 1: Migración templates**

```php
<?php
// database/migrations/2026_04_28_100001_create_templates_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->string('name');
            $table->string('event_type');
            $table->json('default_module_order');
            $table->json('styles');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
```

- [ ] **Step 2: Migración events**

```php
<?php
// database/migrations/2026_04_28_100002_create_events_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('type');
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('templates')->nullOnDelete();
            $table->date('date')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
```

- [ ] **Step 3: Migración event_module_configs**

```php
<?php
// database/migrations/2026_04_28_100003_create_event_module_configs_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('event_module_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('module_key');
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('order');
            $table->timestamps();
            $table->unique(['event_id', 'module_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_module_configs');
    }
};
```

- [ ] **Step 4: Correr migraciones**

```bash
php artisan migrate
```

Esperado: las 3 tablas creadas sin error.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_04_28_100001_create_templates_table.php \
        database/migrations/2026_04_28_100002_create_events_table.php \
        database/migrations/2026_04_28_100003_create_event_module_configs_table.php
git commit -m "feat(events): migraciones templates, events, event_module_configs"
```

---

## Task 3: Modelos y Factories

**Files:**
- Create: `app/Models/Template.php`
- Create: `app/Models/Event.php`
- Create: `app/Models/EventModuleConfig.php`
- Create: `database/factories/TemplateFactory.php`
- Create: `database/factories/EventFactory.php`

- [ ] **Step 1: Modelo Template**

```php
<?php
// app/Models/Template.php
namespace App\Models;

use App\Enums\EventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id', 'name', 'event_type', 'default_module_order', 'styles',
    ];

    protected $casts = [
        'event_type'           => EventType::class,
        'default_module_order' => 'array',
        'styles'               => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Template $template) {
            if (empty($template->public_id)) {
                $template->public_id = (string) Str::ulid();
            }
        });
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
```

- [ ] **Step 2: Modelo Event**

```php
<?php
// app/Models/Event.php
namespace App\Models;

use App\Enums\EventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'name', 'type', 'owner_id', 'template_id', 'date', 'status',
    ];

    protected $casts = [
        'type' => EventType::class,
        'date' => 'date',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function moduleConfigs(): HasMany
    {
        return $this->hasMany(EventModuleConfig::class)->orderBy('order');
    }
}
```

- [ ] **Step 3: Modelo EventModuleConfig**

```php
<?php
// app/Models/EventModuleConfig.php
namespace App\Models;

use App\Enums\ModuleKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventModuleConfig extends Model
{
    protected $fillable = ['event_id', 'module_key', 'enabled', 'order'];

    protected $casts = [
        'module_key' => ModuleKey::class,
        'enabled'    => 'boolean',
        'order'      => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
```

- [ ] **Step 4: TemplateFactory**

```php
<?php
// database/factories/TemplateFactory.php
namespace Database\Factories;

use App\Enums\EventType;
use App\Enums\ModuleKey;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'public_id'            => (string) Str::ulid(),
            'name'                 => $this->faker->words(2, true),
            'event_type'           => $this->faker->randomElement(EventType::cases())->value,
            'default_module_order' => ModuleKey::defaultOrder(),
            'styles'               => [
                'primary_color' => '#c47c5a',
                'accent_color'  => '#f9ede0',
                'font_serif'    => 'Georgia, serif',
                'font_sans'     => 'Inter, sans-serif',
                'bg_image_url'  => null,
            ],
        ];
    }

    public function forWedding(): static
    {
        return $this->state(['event_type' => EventType::Wedding->value]);
    }

    public function forQuinceanera(): static
    {
        return $this->state(['event_type' => EventType::Quinceanera->value]);
    }
}
```

- [ ] **Step 5: EventFactory**

```php
<?php
// database/factories/EventFactory.php
namespace Database\Factories;

use App\Enums\EventType;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EventFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->words(3, true);
        return [
            'slug'        => Str::slug($name) . '-' . Str::random(4),
            'name'        => $name,
            'type'        => $this->faker->randomElement(EventType::cases())->value,
            'owner_id'    => User::factory(),
            'template_id' => null,
            'date'        => $this->faker->dateTimeBetween('+1 month', '+1 year')->format('Y-m-d'),
            'status'      => 'draft',
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => 'published']);
    }
}
```

- [ ] **Step 6: Commit**

```bash
git add app/Models/Template.php app/Models/Event.php app/Models/EventModuleConfig.php \
        database/factories/TemplateFactory.php database/factories/EventFactory.php
git commit -m "feat(events): modelos Template, Event, EventModuleConfig y factories"
```

---

## Task 4: API Resources

**Files:**
- Create: `app/Http/Resources/TemplateResource.php`
- Create: `app/Http/Resources/EventResource.php`
- Create: `app/Http/Resources/EventModuleConfigResource.php`

- [ ] **Step 1: TemplateResource**

```php
<?php
// app/Http/Resources/TemplateResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->public_id,
            'name'                 => $this->name,
            'event_type'           => $this->event_type->value,
            'default_module_order' => $this->default_module_order,
            'styles'               => $this->styles,
            'created_at'           => $this->created_at?->toIso8601String(),
        ];
    }
}
```

- [ ] **Step 2: EventModuleConfigResource**

```php
<?php
// app/Http/Resources/EventModuleConfigResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventModuleConfigResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'module_key' => $this->module_key->value,
            'enabled'    => $this->enabled,
            'order'      => $this->order,
        ];
    }
}
```

- [ ] **Step 3: EventResource**

```php
<?php
// app/Http/Resources/EventResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug'     => $this->slug,
            'name'     => $this->name,
            'type'     => $this->type->value,
            'date'     => $this->date?->toDateString(),
            'status'   => $this->status,
            'template' => $this->whenLoaded('template', fn () => new TemplateResource($this->template)),
            'modules'  => $this->whenLoaded('moduleConfigs', fn () =>
                EventModuleConfigResource::collection($this->moduleConfigs)
            ),
            'owner'    => $this->whenLoaded('owner', fn () => [
                'id'   => $this->owner->public_id,
                'name' => $this->owner->name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add app/Http/Resources/TemplateResource.php \
        app/Http/Resources/EventResource.php \
        app/Http/Resources/EventModuleConfigResource.php
git commit -m "feat(events): API Resources para Template, Event y EventModuleConfig"
```

---

## Task 5: EventService

**Files:**
- Create: `app/Services/EventService.php`

- [ ] **Step 1: Crear EventService**

```php
<?php
// app/Services/EventService.php
namespace App\Services;

use App\Enums\ModuleKey;
use App\Models\Event;
use App\Models\Template;
use App\Models\User;
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
        foreach ($modules as $item) {
            $event->moduleConfigs()
                ->where('module_key', $item['module_key'])
                ->update([
                    'enabled' => $item['enabled'],
                    'order'   => $item['order'],
                ]);
        }
    }

    public function isOwner(User $user, Event $event): bool
    {
        return $event->owner_id === $user->id;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Services/EventService.php
git commit -m "feat(events): EventService con slug, inicialización de módulos y ownership"
```

---

## Task 6: Form Requests

**Files:**
- Create: `app/Http/Requests/Api/V1/StoreTemplateRequest.php`
- Create: `app/Http/Requests/Api/V1/StoreEventRequest.php`
- Create: `app/Http/Requests/Api/V1/UpdateEventRequest.php`
- Create: `app/Http/Requests/Api/V1/UpdateModulesRequest.php`

- [ ] **Step 1: StoreTemplateRequest**

```php
<?php
// app/Http/Requests/Api/V1/StoreTemplateRequest.php
namespace App\Http\Requests\Api\V1;

use App\Enums\EventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTemplateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'                          => ['required', 'string', 'max:120'],
            'event_type'                    => ['required', Rule::enum(EventType::class)],
            'default_module_order'          => ['required', 'array', 'min:1'],
            'default_module_order.*'        => ['string'],
            'styles'                        => ['required', 'array'],
            'styles.primary_color'          => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'styles.accent_color'           => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'styles.font_serif'             => ['required', 'string', 'max:120'],
            'styles.font_sans'              => ['required', 'string', 'max:120'],
            'styles.bg_image_url'           => ['nullable', 'url', 'max:500'],
        ];
    }
}
```

- [ ] **Step 2: StoreEventRequest**

```php
<?php
// app/Http/Requests/Api/V1/StoreEventRequest.php
namespace App\Http\Requests\Api\V1;

use App\Enums\EventType;
use App\Models\Template;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:200'],
            'type'        => ['required', Rule::enum(EventType::class)],
            'template_id' => ['nullable', 'string', Rule::exists('templates', 'public_id')],
            'date'        => ['nullable', 'date', 'after:today'],
        ];
    }
}
```

- [ ] **Step 3: UpdateEventRequest**

```php
<?php
// app/Http/Requests/Api/V1/UpdateEventRequest.php
namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'   => ['sometimes', 'string', 'max:200'],
            'date'   => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', Rule::in(['draft', 'published'])],
            'template_id' => ['sometimes', 'nullable', 'string', Rule::exists('templates', 'public_id')],
        ];
    }
}
```

- [ ] **Step 4: UpdateModulesRequest**

```php
<?php
// app/Http/Requests/Api/V1/UpdateModulesRequest.php
namespace App\Http\Requests\Api\V1;

use App\Enums\ModuleKey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateModulesRequest extends FormRequest
{
    public function authorize(): bool { return true; }

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
```

- [ ] **Step 5: Commit**

```bash
git add app/Http/Requests/Api/V1/StoreTemplateRequest.php \
        app/Http/Requests/Api/V1/StoreEventRequest.php \
        app/Http/Requests/Api/V1/UpdateEventRequest.php \
        app/Http/Requests/Api/V1/UpdateModulesRequest.php
git commit -m "feat(events): Form Requests para templates, events y modules"
```

---

## Task 7: Controllers y Rutas

**Files:**
- Create: `app/Http/Controllers/Api/V1/TemplateController.php`
- Create: `app/Http/Controllers/Api/V1/EventController.php`
- Create: `routes/api/v1/events.php`
- Create: `routes/api/v1/admin.templates.php`
- Modify: `routes/api.php`

- [ ] **Step 1: TemplateController**

```php
<?php
// app/Http/Controllers/Api/V1/TemplateController.php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTemplateRequest;
use App\Http\Resources\TemplateResource;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Template::query();

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        $templates = $query->orderBy('name')->get();

        return response()->json([
            'ok'   => true,
            'data' => TemplateResource::collection($templates),
        ]);
    }

    public function store(StoreTemplateRequest $request): JsonResponse
    {
        $template = Template::create($request->validated());

        return response()->json([
            'ok'   => true,
            'data' => new TemplateResource($template),
        ], 201);
    }

    public function update(StoreTemplateRequest $request, Template $template): JsonResponse
    {
        $template->update($request->validated());

        return response()->json([
            'ok'   => true,
            'data' => new TemplateResource($template),
        ]);
    }
}
```

- [ ] **Step 2: EventController**

```php
<?php
// app/Http/Controllers/Api/V1/EventController.php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreEventRequest;
use App\Http\Requests\Api\V1\UpdateEventRequest;
use App\Http\Requests\Api\V1\UpdateModulesRequest;
use App\Http\Resources\EventModuleConfigResource;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\Template;
use App\Services\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function __construct(private readonly EventService $eventService) {}

    public function show(string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)
            ->with(['template', 'moduleConfigs'])
            ->firstOrFail();

        return response()->json([
            'ok'   => true,
            'data' => new EventResource($event),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $events = Event::where('owner_id', $request->user('api')->id)
            ->with('template')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'ok'   => true,
            'data' => EventResource::collection($events),
        ]);
    }

    public function store(StoreEventRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['template_id'])) {
            $template = Template::where('public_id', $data['template_id'])->first();
            $data['template_id'] = $template?->id;
        }

        $event = $this->eventService->create($request->user('api'), $data);
        $event->load(['template', 'moduleConfigs']);

        return response()->json([
            'ok'   => true,
            'data' => new EventResource($event),
        ], 201);
    }

    public function update(UpdateEventRequest $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->eventService->isOwner($request->user('api'), $event)) {
            return response()->json([
                'ok'    => false,
                'error' => ['code' => 'AUTH_FORBIDDEN', 'message' => 'No tienes permiso sobre este evento.', 'details' => null],
            ], 403);
        }

        $data = $request->validated();

        if (array_key_exists('template_id', $data) && $data['template_id'] !== null) {
            $template = Template::where('public_id', $data['template_id'])->first();
            $data['template_id'] = $template?->id;
        }

        $event->update($data);

        return response()->json([
            'ok'   => true,
            'data' => new EventResource($event->fresh(['template', 'moduleConfigs'])),
        ]);
    }

    public function destroy(Request $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->eventService->isOwner($request->user('api'), $event)) {
            return response()->json([
                'ok'    => false,
                'error' => ['code' => 'AUTH_FORBIDDEN', 'message' => 'No tienes permiso sobre este evento.', 'details' => null],
            ], 403);
        }

        $event->delete();

        return response()->json(['ok' => true, 'data' => null]);
    }

    public function getModules(Request $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->eventService->isOwner($request->user('api'), $event)) {
            return response()->json([
                'ok'    => false,
                'error' => ['code' => 'AUTH_FORBIDDEN', 'message' => 'No tienes permiso sobre este evento.', 'details' => null],
            ], 403);
        }

        return response()->json([
            'ok'   => true,
            'data' => EventModuleConfigResource::collection($event->moduleConfigs),
        ]);
    }

    public function updateModules(UpdateModulesRequest $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->firstOrFail();

        if (! $this->eventService->isOwner($request->user('api'), $event)) {
            return response()->json([
                'ok'    => false,
                'error' => ['code' => 'AUTH_FORBIDDEN', 'message' => 'No tienes permiso sobre este evento.', 'details' => null],
            ], 403);
        }

        $this->eventService->updateModules($event, $request->validated('modules'));

        return response()->json([
            'ok'   => true,
            'data' => EventModuleConfigResource::collection($event->moduleConfigs()->get()),
        ]);
    }
}
```

- [ ] **Step 3: Ruta events.php**

```php
<?php
// routes/api/v1/events.php
use App\Http\Controllers\Api\V1\EventController;
use Illuminate\Support\Facades\Route;

// Rutas públicas (sin auth)
Route::get('events/{slug}', [EventController::class, 'show']);

// Rutas del master (requieren auth)
Route::middleware(['auth:api', 'user.active', 'user.verified', 'jwt.not_revoked'])->group(function () {
    Route::get('events', [EventController::class, 'index']);
    Route::post('events', [EventController::class, 'store']);
    Route::put('events/{slug}', [EventController::class, 'update']);
    Route::delete('events/{slug}', [EventController::class, 'destroy']);
    Route::get('events/{slug}/modules', [EventController::class, 'getModules']);
    Route::put('events/{slug}/modules', [EventController::class, 'updateModules']);
});
```

- [ ] **Step 4: Ruta admin.templates.php**

```php
<?php
// routes/api/v1/admin.templates.php
use App\Http\Controllers\Api\V1\TemplateController;
use Illuminate\Support\Facades\Route;

// GET templates es público (el wizard lo necesita sin login)
Route::get('templates', [TemplateController::class, 'index']);

// CRUD de templates solo para admin (role:admin via Spatie middleware)
Route::middleware(['auth:api', 'user.active', 'user.verified', 'jwt.not_revoked', 'role:admin'])->group(function () {
    Route::post('templates', [TemplateController::class, 'store']);
    Route::put('templates/{template:public_id}', [TemplateController::class, 'update']);
});
```

- [ ] **Step 5: Registrar rutas en api.php**

Añadir dentro del bloque `Route::prefix('v1')->group(...)` en `routes/api.php`, después de las rutas de appointments:

```php
if (config('kaan.features.events', true)) {
    require __DIR__ . '/api/v1/events.php';
    require __DIR__ . '/api/v1/admin.templates.php';
}
```

- [ ] **Step 6: Verificar rutas registradas**

```bash
php artisan route:list --path=events
php artisan route:list --path=templates
```

Esperado: 6 rutas de events y 3 de templates listadas.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Api/V1/TemplateController.php \
        app/Http/Controllers/Api/V1/EventController.php \
        routes/api/v1/events.php \
        routes/api/v1/admin.templates.php \
        routes/api.php
git commit -m "feat(events): controllers EventController y TemplateController, rutas registradas"
```

---

## Task 8: Seeder de Templates

**Files:**
- Create: `database/seeders/TemplatesSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`

- [ ] **Step 1: Crear TemplatesSeeder**

```php
<?php
// database/seeders/TemplatesSeeder.php
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
```

- [ ] **Step 2: Registrar en DatabaseSeeder**

En `database/seeders/DatabaseSeeder.php`, añadir dentro de `run()`:

```php
$this->call(TemplatesSeeder::class);
```

- [ ] **Step 3: Correr seeder**

```bash
php artisan db:seed --class=TemplatesSeeder
```

Esperado: 3 templates insertados sin error.

- [ ] **Step 4: Commit**

```bash
git add database/seeders/TemplatesSeeder.php database/seeders/DatabaseSeeder.php
git commit -m "feat(events): TemplatesSeeder con 3 templates base (Toscana, Esmeralda, Solsticio)"
```

---

## Task 9: Tests — Templates

**Files:**
- Create: `tests/Feature/Events/TemplatesTest.php`

- [ ] **Step 1: Escribir el test**

```php
<?php
// tests/Feature/Events/TemplatesTest.php
declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Models\Template;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TemplatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(TemplatesSeeder::class);
    }

    private function loginToken(User $user): string
    {
        $res = $this->postJson('/api/v1/auth/login', [
            'login'    => $user->email,
            'password' => 'Secret123456',
        ]);
        return $res->json('data.access_token');
    }

    private function authHeader(string $token): array
    {
        return ['Authorization' => "Bearer {$token}"];
    }

    private function makeAdmin(): User
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'status'             => 'active',
            'password'           => 'Secret123456',
            'email_verified_at'  => now(),
        ]);
        $admin->assignRole('admin');
        return $admin;
    }

    public function test_public_can_list_templates(): void
    {
        $response = $this->getJson('/api/v1/templates');

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_public_can_filter_templates_by_event_type(): void
    {
        $response = $this->getJson('/api/v1/templates?event_type=wedding');

        $response->assertStatus(200)
            ->assertJsonPath('ok', true);

        foreach ($response->json('data') as $template) {
            $this->assertEquals('wedding', $template['event_type']);
        }
    }

    public function test_template_response_has_required_fields(): void
    {
        $response = $this->getJson('/api/v1/templates');

        $response->assertJsonStructure([
            'ok',
            'data' => [['id', 'name', 'event_type', 'default_module_order', 'styles']],
        ]);
    }

    public function test_admin_can_create_template(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->loginToken($admin);

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson('/api/v1/templates', [
                'name'                 => 'Test Template',
                'event_type'           => 'birthday',
                'default_module_order' => ['rsvp', 'gifts'],
                'styles'               => [
                    'primary_color' => '#ff0000',
                    'accent_color'  => '#ffeeee',
                    'font_serif'    => 'Georgia, serif',
                    'font_sans'     => 'Inter, sans-serif',
                    'bg_image_url'  => null,
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.name', 'Test Template')
            ->assertJsonPath('data.event_type', 'birthday');
    }

    public function test_non_admin_cannot_create_template(): void
    {
        $user  = User::factory()->create(['status' => 'active', 'password' => 'Secret123456', 'email_verified_at' => now()]);
        $token = $this->loginToken($user);

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson('/api/v1/templates', [
                'name'                 => 'Hack',
                'event_type'           => 'wedding',
                'default_module_order' => ['rsvp'],
                'styles'               => ['primary_color' => '#000000', 'accent_color' => '#ffffff', 'font_serif' => 'x', 'font_sans' => 'y', 'bg_image_url' => null],
            ]);

        $response->assertStatus(403);
    }

    public function test_create_template_validates_color_format(): void
    {
        $admin = $this->makeAdmin();
        $token = $this->loginToken($admin);

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson('/api/v1/templates', [
                'name'                 => 'Bad Color',
                'event_type'           => 'wedding',
                'default_module_order' => ['rsvp'],
                'styles'               => [
                    'primary_color' => 'not-a-color',
                    'accent_color'  => '#ffffff',
                    'font_serif'    => 'Georgia, serif',
                    'font_sans'     => 'Inter, sans-serif',
                    'bg_image_url'  => null,
                ],
            ]);

        $response->assertStatus(422);
    }
}
```

- [ ] **Step 2: Correr y verificar que pasan**

```bash
php artisan test tests/Feature/Events/TemplatesTest.php --testdox
```

Esperado: todos los tests en verde.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Events/TemplatesTest.php
git commit -m "test(events): tests de Templates (list, filter, create, permisos)"
```

---

## Task 10: Tests — Event CRUD

**Files:**
- Create: `tests/Feature/Events/EventCrudTest.php`

- [ ] **Step 1: Escribir el test**

```php
<?php
// tests/Feature/Events/EventCrudTest.php
declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Models\Event;
use App\Models\Template;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TemplatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(TemplatesSeeder::class);
    }

    private function loginToken(User $user): string
    {
        $res = $this->postJson('/api/v1/auth/login', [
            'login'    => $user->email,
            'password' => 'Secret123456',
        ]);
        return $res->json('data.access_token');
    }

    private function authHeader(string $token): array
    {
        return ['Authorization' => "Bearer {$token}"];
    }

    private function makeMaster(): User
    {
        /** @var User $master */
        $master = User::factory()->create([
            'status'            => 'active',
            'password'          => 'Secret123456',
            'email_verified_at' => now(),
        ]);
        $master->assignRole('master');
        return $master;
    }

    public function test_master_can_create_event(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson('/api/v1/events', [
                'name' => 'Boda de Sofía y Mateo',
                'type' => 'wedding',
                'date' => now()->addMonths(3)->toDateString(),
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.name', 'Boda de Sofía y Mateo')
            ->assertJsonPath('data.type', 'wedding')
            ->assertJsonPath('data.status', 'draft');

        $this->assertNotNull($response->json('data.slug'));
        $this->assertNotNull($response->json('data.modules'));
    }

    public function test_event_modules_initialized_on_create(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);

        $response = $this->withHeaders($this->authHeader($token))
            ->postJson('/api/v1/events', [
                'name' => 'XV de Valentina',
                'type' => 'quinceanera',
            ]);

        $response->assertStatus(201);
        $modules = $response->json('data.modules');

        $this->assertNotEmpty($modules);
        $moduleKeys = array_column($modules, 'module_key');
        $this->assertContains('rsvp', $moduleKeys);
        $this->assertContains('gifts', $moduleKeys);
    }

    public function test_public_can_view_published_event(): void
    {
        $master = $this->makeMaster();
        $event  = Event::factory()->published()->create(['owner_id' => $master->id]);

        $response = $this->getJson("/api/v1/events/{$event->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.slug', $event->slug);
    }

    public function test_public_can_view_draft_event(): void
    {
        $master = $this->makeMaster();
        $event  = Event::factory()->create(['owner_id' => $master->id, 'status' => 'draft']);

        $response = $this->getJson("/api/v1/events/{$event->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true);
    }

    public function test_master_can_list_own_events(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        Event::factory()->count(3)->create(['owner_id' => $master->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->getJson('/api/v1/events');

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_master_cannot_see_other_masters_events(): void
    {
        $master1 = $this->makeMaster();
        $master2 = $this->makeMaster();
        $token1  = $this->loginToken($master1);
        Event::factory()->count(2)->create(['owner_id' => $master2->id]);

        $response = $this->withHeaders($this->authHeader($token1))
            ->getJson('/api/v1/events');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_master_can_update_own_event(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->putJson("/api/v1/events/{$event->slug}", ['name' => 'Nombre actualizado']);

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.name', 'Nombre actualizado');
    }

    public function test_master_cannot_update_other_masters_event(): void
    {
        $master1 = $this->makeMaster();
        $master2 = $this->makeMaster();
        $token1  = $this->loginToken($master1);
        $event   = Event::factory()->create(['owner_id' => $master2->id]);

        $response = $this->withHeaders($this->authHeader($token1))
            ->putJson("/api/v1/events/{$event->slug}", ['name' => 'Hack']);

        $response->assertStatus(403)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    public function test_master_can_delete_own_event(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        $event  = Event::factory()->create(['owner_id' => $master->id]);

        $response = $this->withHeaders($this->authHeader($token))
            ->deleteJson("/api/v1/events/{$event->slug}");

        $response->assertStatus(200)->assertJsonPath('ok', true);
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    public function test_unauthenticated_cannot_create_event(): void
    {
        $response = $this->postJson('/api/v1/events', [
            'name' => 'Sin auth',
            'type' => 'wedding',
        ]);

        $response->assertStatus(401);
    }

    public function test_event_response_has_required_fields(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);

        $this->withHeaders($this->authHeader($token))
            ->postJson('/api/v1/events', ['name' => 'Evento', 'type' => 'party']);

        $slug = Event::where('owner_id', $master->id)->first()->slug;

        $response = $this->getJson("/api/v1/events/{$slug}");
        $response->assertJsonStructure([
            'ok',
            'data' => ['slug', 'name', 'type', 'status', 'modules'],
        ]);
    }
}
```

- [ ] **Step 2: Correr y verificar que pasan**

```bash
php artisan test tests/Feature/Events/EventCrudTest.php --testdox
```

Esperado: todos los tests en verde.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Events/EventCrudTest.php
git commit -m "test(events): tests de EventCrud (create, list, update, delete, permisos)"
```

---

## Task 11: Tests — Event Modules

**Files:**
- Create: `tests/Feature/Events/EventModulesTest.php`

- [ ] **Step 1: Escribir el test**

```php
<?php
// tests/Feature/Events/EventModulesTest.php
declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Models\Event;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function loginToken(User $user): string
    {
        $res = $this->postJson('/api/v1/auth/login', [
            'login'    => $user->email,
            'password' => 'Secret123456',
        ]);
        return $res->json('data.access_token');
    }

    private function authHeader(string $token): array
    {
        return ['Authorization' => "Bearer {$token}"];
    }

    private function makeMaster(): User
    {
        /** @var User $master */
        $master = User::factory()->create([
            'status'            => 'active',
            'password'          => 'Secret123456',
            'email_verified_at' => now(),
        ]);
        $master->assignRole('master');
        return $master;
    }

    private function createEventWithModules(User $master, string $token): array
    {
        $res = $this->withHeaders($this->authHeader($token))
            ->postJson('/api/v1/events', ['name' => 'Evento', 'type' => 'wedding']);
        return [$res->json('data.slug'), $res->json('data.modules')];
    }

    public function test_master_can_get_modules(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        [$slug] = $this->createEventWithModules($master, $token);

        $response = $this->withHeaders($this->authHeader($token))
            ->getJson("/api/v1/events/{$slug}/modules");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true);

        $modules = $response->json('data');
        $this->assertNotEmpty($modules);
    }

    public function test_master_can_update_module_order_and_enabled(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        [$slug, $modules] = $this->createEventWithModules($master, $token);

        $updated = array_map(function ($module, $index) {
            return [
                'module_key' => $module['module_key'],
                'enabled'    => $index !== 0,
                'order'      => count($modules) - 1 - $index,
            ];
        }, $modules, array_keys($modules));

        $response = $this->withHeaders($this->authHeader($token))
            ->putJson("/api/v1/events/{$slug}/modules", ['modules' => $updated]);

        $response->assertStatus(200)
            ->assertJsonPath('ok', true);
    }

    public function test_module_update_validates_module_key(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        [$slug] = $this->createEventWithModules($master, $token);

        $response = $this->withHeaders($this->authHeader($token))
            ->putJson("/api/v1/events/{$slug}/modules", [
                'modules' => [
                    ['module_key' => 'fake_module', 'enabled' => true, 'order' => 0],
                ],
            ]);

        $response->assertStatus(422);
    }

    public function test_non_owner_cannot_get_modules(): void
    {
        $master1 = $this->makeMaster();
        $master2 = $this->makeMaster();
        $token2  = $this->loginToken($master2);
        $event   = Event::factory()->create(['owner_id' => $master1->id]);

        $response = $this->withHeaders($this->authHeader($token2))
            ->getJson("/api/v1/events/{$event->slug}/modules");

        $response->assertStatus(403)
            ->assertJsonPath('error.code', 'AUTH_FORBIDDEN');
    }

    public function test_modules_response_has_required_fields(): void
    {
        $master = $this->makeMaster();
        $token  = $this->loginToken($master);
        [$slug] = $this->createEventWithModules($master, $token);

        $response = $this->withHeaders($this->authHeader($token))
            ->getJson("/api/v1/events/{$slug}/modules");

        $response->assertJsonStructure([
            'ok',
            'data' => [['module_key', 'enabled', 'order']],
        ]);
    }
}
```

- [ ] **Step 2: Correr todos los tests del dominio Events**

```bash
php artisan test tests/Feature/Events/ --testdox
```

Esperado: todos en verde.

- [ ] **Step 3: Correr suite completa para detectar regresiones**

```bash
php artisan test --testdox
```

Esperado: suite completa en verde.

- [ ] **Step 4: Commit final**

```bash
git add tests/Feature/Events/EventModulesTest.php
git commit -m "test(events): tests de EventModules (get, update, validación, permisos)"
```

---

## Task 12: docsSync — Actualizar CONTRACTS.md

- [ ] **Step 1: Añadir sección de endpoints del dominio Events en `kaan-core-frontend/docs/CONTRACTS.md`**

Usar el agente `docsSync` para ejecutar este paso. El agente leerá los controllers implementados y actualizará el archivo con los campos garantizados reales.

Endpoints a documentar:

| Endpoint | Mínimo garantizado |
|---|---|
| `GET /events/:slug` (200) | `data.slug`, `data.name`, `data.type`, `data.status`, `data.modules[]` |
| `GET /events` (200) | Paginación estándar · cada item: `slug`, `name`, `type`, `status` |
| `POST /events` (201) | `data.slug`, `data.name`, `data.type`, `data.status`, `data.modules[]` |
| `PUT /events/:slug` (200) | `data.slug`, `data.name`, `data.type`, `data.status` |
| `DELETE /events/:slug` (200) | `data: null` |
| `GET /events/:slug/modules` (200) | `data[].module_key`, `data[].enabled`, `data[].order` |
| `PUT /events/:slug/modules` (200) | `data[].module_key`, `data[].enabled`, `data[].order` |
| `GET /templates` (200) | `data[].id`, `data[].name`, `data[].event_type`, `data[].default_module_order`, `data[].styles` |
| `POST /templates` (201) | `data.id`, `data.name`, `data.event_type` |
| `PUT /templates/:id` (200) | `data.id`, `data.name`, `data.event_type` |

- [ ] **Step 2: Commit en frontend repo**

```bash
cd ../kaan-core-frontend
git add docs/CONTRACTS.md
git commit -m "docs: añade contratos del dominio Events (Plan 1)"
```

---

## Verificación final del Plan 1

- [ ] `php artisan test tests/Feature/Events/ --testdox` — todos en verde
- [ ] `php artisan test --testdox` — suite completa sin regresiones
- [ ] `php artisan route:list --path=events` — 6 rutas listadas
- [ ] `php artisan route:list --path=templates` — 3 rutas listadas
- [ ] `php artisan db:seed --class=TemplatesSeeder` — sin error
- [ ] `docs/CONTRACTS.md` del frontend actualizado con endpoints del dominio Events
