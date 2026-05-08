<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\AuditLogger;
use App\Services\Auth\JwtSessionIssuer;
use App\Traits\HasApiResponse;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use App\Models\Role;

class RegisterController extends Controller
{
    use HasApiResponse;

    public function store(RegisterRequest $request, JwtSessionIssuer $issuer)
    {
        // --- Feature flag ---
        if (! config('kaan.auth.allow_public_registration', false)) {
            return $this->error(
                'REGISTRATION_DISABLED',
                'El registro público no está habilitado.',
                403
            );
        }

        $defaultStatus = config('kaan.auth.registration_default_status', 'active');
        $defaultRole   = config('kaan.auth.default_role', 'user');
        $issueToken    = config('kaan.auth.register_issue_token', true);
        $requireVerify = config('kaan.auth.require_verified_email', false);

        // --- Profile allowlist ---
        $profileAllowlist = ['phone', 'company'];
        $profileData = $request->has('profile')
            ? array_intersect_key($request->input('profile'), array_flip($profileAllowlist))
            : [];

        try {
            $user = DB::transaction(function () use ($request, $defaultStatus, $defaultRole, $profileData) {
                $user = User::create([
                    'name'     => $request->input('name', ''),
                    'email'    => $request->input('email'),
                    'password' => $request->input('password'),
                    'status'   => $defaultStatus,
                ]);

                // Assign default role (guard api) — RBAC is a core capability
                $role = Role::findOrCreate($defaultRole, 'api');
                $user->syncRoles([$role]);

                // Persist profile in user_profiles.meta with allowlist
                if (! empty($profileData)) {
                    UserProfile::updateOrCreate(
                        ['user_id' => $user->id],
                        ['meta'    => $profileData]
                    );
                }

                // Audit without PII
                AuditLogger::log('auth.registered', $user, [
                    'user_public_id' => $user->public_id,
                    'status'         => $user->status,
                    'source'         => 'public_registration',
                ], $user);

                return $user;
            });
        } catch (QueryException $e) {
            // Race condition: concurrent duplicate email insert
            if ($this->isDuplicateEntryException($e)) {
                return $this->error(
                    'VALIDATION_ERROR',
                    'Error de validación.',
                    422,
                    ['email' => ['El correo electrónico ya ha sido registrado.']]
                );
            }
            throw $e;
        }

        // Send email verification (afterCommit for safety)
        if ($requireVerify) {
            $user->sendEmailVerificationNotification();
        }

        // Build response
        $responseData = [
            'user' => [
                'id'     => $user->public_id,
                'name'   => $user->name,
                'email'  => $user->email,
                'status' => $user->status,
            ],
            'requires_email_verification' => $requireVerify,
        ];

        // Issue token only when status is active and flag is ON
        if ($issueToken && $user->status === 'active') {
            $tokenData = $issuer->issue($user, $request);
            $responseData = array_merge($responseData, $tokenData);
        }

        return $this->success($responseData, 201);
    }

    /**
     * Detect MySQL / SQLite / Postgres duplicate-entry exceptions.
     */
    private function isDuplicateEntryException(QueryException $e): bool
    {
        $code = (string) $e->getCode();

        // MySQL: 23000, SQLite: 23000, Postgres: 23505
        return in_array($code, ['23000', '23505'], true);
    }
}
