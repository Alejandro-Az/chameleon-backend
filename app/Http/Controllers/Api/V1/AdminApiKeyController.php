<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreApiKeyRequest;
use App\Http\Resources\ApiKeyResource;
use App\Models\ApiKey;
use App\Models\AuthSession;
use App\Models\User;
use App\Services\ApiKeys\ApiKeyService;
use Illuminate\Http\Request;

class AdminApiKeyController extends Controller
{
    use \App\Traits\HasApiResponse;
    use \App\Traits\HasPaginationPolicy;

    public function __construct(private readonly ApiKeyService $service) {}

    public function index(Request $request, User $serviceAccount)
    {
        $q = ApiKey::query()->where('user_id', $serviceAccount->id);

        if ($search = trim((string) $request->query('q', ''))) {
            $q->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('prefix', 'like', "%{$search}%")
                    ->orWhere('public_id', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            if ($status === 'active') {
                $q->active();
            } elseif ($status === 'revoked') {
                $q->whereNotNull('revoked_at');
            } elseif ($status === 'expired') {
                $q->whereNull('revoked_at')
                  ->whereNotNull('expires_at')
                  ->where('expires_at', '<=', now());
            }
        }

        $perPage = $this->resolvePerPage($request);
        $page = $q->latest()->paginate($perPage);

        $payload = ApiKeyResource::collection($page)->response()->getData(true);

        return $this->success($payload);
    }

    public function store(StoreApiKeyRequest $request, User $serviceAccount)
    {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        $result = $this->service->generate(
            serviceAccount: $serviceAccount,
            name: $data['name'],
            scopes: $data['scopes'] ?? [],
            expiresAt: isset($data['expires_at']) ? \Carbon\Carbon::parse($data['expires_at']) : null,
            createdBy: $actor
        );

        /** @var ApiKey $apiKey */
        $apiKey = $result['api_key'];

        \App\Services\AuditLogger::log('api_key.created', $apiKey, [
            'service_account' => $serviceAccount->public_id,
            'api_key_id'      => $apiKey->public_id,
            'prefix'          => $apiKey->prefix,
        ]);

        return $this->success([
            'api_key' => new ApiKeyResource($apiKey),
            'secret'  => $result['secret'], // one-time
        ], 201);
    }

    public function rotate(ApiKey $apiKey, Request $request)
    {
        /** @var User $actor */
        $actor = $request->user();

        $oldPrefix = $apiKey->prefix;

        $result = $this->service->rotate($apiKey, $actor);
        /** @var ApiKey $newKey */
        $newKey = $result['api_key'];

        \App\Services\AuditLogger::log('api_key.rotated', $newKey, [
            'from_prefix' => $oldPrefix,
            'to_prefix'   => $newKey->prefix,
        ]);

        return $this->success([
            'api_key' => new ApiKeyResource($newKey),
            'secret'  => $result['secret'],
        ]);
    }

    public function destroy(ApiKey $apiKey)
    {
        $this->service->revoke($apiKey);

        // Revocación quirúrgica: solo sesiones activas generadas por esta API key
        $sessionsRevoked = AuthSession::query()
            ->where('api_key_id', $apiKey->id)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->update(['revoked_at' => now()]);

        \App\Services\AuditLogger::log('api_key.revoked', $apiKey, [
            'api_key_id'       => $apiKey->public_id,
            'prefix'           => $apiKey->prefix,
            'sessions_revoked' => $sessionsRevoked,
        ]);

        return $this->success(['message' => 'API key revocada correctamente.']);
    }
}
