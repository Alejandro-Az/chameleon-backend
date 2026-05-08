<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Auth\Events\Verified;
use App\Services\AuditLogger;
use App\Traits\HasApiResponse;

class EmailVerificationController extends Controller
{
    use HasApiResponse;

    public function verify(Request $request, $id, $hash)
    {
        $frontendUrl = rtrim(Config::get('kaan.frontend.verify_email_url'), '/');
        $wantsJson = $request->wantsJson();
        
        if (empty($frontendUrl)) {
            if ($wantsJson) {
                return $this->error('CONFIG_VERIFY_EMAIL_URL_MISSING', 'Verification URL is not configured.', 500);
            }
            return redirect()->away(Config::get('app.url') . '?error=verification_config_missing');
        }

        $errorResponse = function($reason) use ($wantsJson, $frontendUrl) {
            if ($wantsJson) {
                return $this->error('AUTH_VERIFICATION_INVALID', $reason, 403);
            }
            return redirect()->away($frontendUrl . '?verified=0&reason=' . $reason);
        };

        if (! $request->hasValidSignature()) {
            $reason = 'invalid_signature';
            if ($request->query('expires')) {
                $reason = ((int) $request->query('expires') < time()) ? 'expired' : 'invalid_signature';
            }
            return $errorResponse($reason);
        }

        $user = User::where('public_id', $id)->first();

        if (! $user) {
            return $errorResponse('invalid_user');
        }

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return $errorResponse('hash_mismatch');
        }

        if ($user->hasVerifiedEmail()) {
            if ($wantsJson) {
                return $this->success(['verified' => true, 'message' => 'Already verified']);
            }
            return redirect()->away($frontendUrl . '?verified=1');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            
            AuditLogger::log('auth.email_verified', $user, [
                'user_public_id' => $user->public_id,
                'mode' => 'verified',
            ], $user);
        }

        if ($wantsJson) {
            return $this->success(['verified' => true, 'message' => 'Email verified successfully.']);
        }

        return redirect()->away($frontendUrl . '?verified=1');
    }

    public function status(Request $request)
    {
        return $this->success([
            'verified' => $request->user()->hasVerifiedEmail(),
        ]);
    }
}
