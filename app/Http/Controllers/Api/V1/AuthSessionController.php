<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ListSessionsRequest;
use App\Http\Resources\AuthSessionResource;
use App\Models\AuthSession;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthSessionController extends Controller
{
    use \App\Traits\HasApiResponse;
    use \App\Traits\HasPaginationPolicy;

    public function index(ListSessionsRequest $request)
    {
        $user = $request->user();
        $includeRevoked = $request->validated('include_revoked', false);
        $perPage = $this->resolvePerPage($request, $request->validated('per_page'));

        $query = AuthSession::where('user_id', $user->id)
            ->orderBy('last_seen_at', 'desc');

        if (!$includeRevoked) {
            $query->whereNull('revoked_at')
                  ->where('expires_at', '>', now());
        }

        $sessions = $query->paginate($perPage);

        $payload = AuthSessionResource::collection($sessions)->response()->getData(true);

        return $this->success($payload);
    }

    public function destroy(AuthSession $session, Request $request)
    {
        $user = $request->user();

        // Ownership: force ModelNotFound path to keep NOT_FOUND contract code.
        if ($session->user_id !== $user->id) {
            throw (new ModelNotFoundException())->setModel(AuthSession::class, [$session->getKey()]);
        }

        $wasAlreadyRevoked = !is_null($session->revoked_at);

        if (!$wasAlreadyRevoked) {
            $session->update(['revoked_at' => now()]);

            AuditLogger::log('auth.session.revoked', null, [
                'session_public_id' => $session->public_id,
            ], $user);
        }

        return $this->success([
            'revoked' => true,
            'was_already_revoked' => $wasAlreadyRevoked,
        ]);
    }

    public function revokeOthers(Request $request)
    {
        $user = $request->user();
        $currentJti = (string) $request->attributes->get('current_jti');

        $revokedCount = AuthSession::where('user_id', $user->id)
            ->where('token_id', '!=', $currentJti)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        if ($revokedCount > 0) {
            AuditLogger::log('auth.sessions.revoked_others', null, [
                'revoked_count' => $revokedCount,
            ], $user);
        }

        return $this->success(['revoked_count' => $revokedCount]);
    }

    public function revokeAll(Request $request)
    {
        $user = $request->user();
        
        $revokedCount = AuthSession::where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        if ($revokedCount > 0) {
            AuditLogger::log('auth.sessions.revoked_all', null, [
                'revoked_count' => $revokedCount,
            ], $user);
        }

        try {
            JWTAuth::parseToken()->invalidate();
        } catch (\Exception $e) {
            // Already invalid, safe to ignore
        }

        return $this->success(['revoked_count' => $revokedCount]);
    }
}
