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
    /**
     * @OA\Get(
     *     path="/api/v1/templates",
     *     tags={"Templates"},
     *     summary="Listar templates disponibles",
     *     description="Acceso público. Retorna templates para crear eventos.",
     *     @OA\Parameter(name="event_type", in="query", required=false,
     *         @OA\Schema(type="string", enum={"wedding","xv","graduation","birthday","other"})),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de templates",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="public_id", type="string"),
     *                     @OA\Property(property="name", type="string", example="Toscana"),
     *                     @OA\Property(property="event_type", type="string"),
     *                     @OA\Property(property="default_module_order", type="array",
     *                         @OA\Items(type="string")),
     *                     @OA\Property(property="styles", type="object")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/v1/templates",
     *     tags={"Templates"},
     *     summary="Crear template (solo admin)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","event_type","default_module_order"},
     *             @OA\Property(property="name", type="string", example="Mediterráneo"),
     *             @OA\Property(property="event_type", type="string",
     *                 enum={"wedding","xv","graduation","birthday","other"}),
     *             @OA\Property(property="default_module_order", type="array",
     *                 @OA\Items(type="string", example="rsvp")),
     *             @OA\Property(property="styles", type="object")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Template creado"),
     *     @OA\Response(response=403, description="Solo admins"),
     *     @OA\Response(response=422, description="Validación fallida")
     * )
     */
    public function store(StoreTemplateRequest $request): JsonResponse
    {
        $template = Template::create($request->validated());

        return response()->json([
            'ok'   => true,
            'data' => new TemplateResource($template),
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/templates/{public_id}",
     *     tags={"Templates"},
     *     summary="Actualizar template (solo admin)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="public_id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="default_module_order", type="array",
     *                 @OA\Items(type="string")),
     *             @OA\Property(property="styles", type="object")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Template actualizado"),
     *     @OA\Response(response=403, description="Solo admins"),
     *     @OA\Response(response=404, description="Template no encontrado")
     * )
     */
    public function update(UpdateTemplateRequest $request, Template $template): JsonResponse
    {
        $template->update($request->validated());

        return response()->json([
            'ok'   => true,
            'data' => new TemplateResource($template),
        ]);
    }
}
