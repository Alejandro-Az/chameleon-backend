<?php
// app/Http/Controllers/Api/V1/TemplateController.php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTemplateRequest;
use App\Http\Requests\Api\V1\UpdateTemplateRequest;
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

    public function update(UpdateTemplateRequest $request, Template $template): JsonResponse
    {
        $template->update($request->validated());

        return response()->json([
            'ok'   => true,
            'data' => new TemplateResource($template),
        ]);
    }
}
