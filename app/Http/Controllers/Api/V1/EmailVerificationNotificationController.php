<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use App\Services\AuditLogger;
use App\Notifications\VerifyEmailLinkNotification;
use App\Traits\HasApiResponse;

class EmailVerificationNotificationController extends Controller
{
    use HasApiResponse;

    public function resend(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->success(['verified' => true, 'message' => 'Email is already verified.']);
        }

        // Dual Rate Limiting: By User ID (Primary) and User ID + IP (Secondary)
        $primaryKey = 'verify-email-resend:' . $user->id;
        $secondaryKey = 'verify-email-resend:' . $user->id . ':' . $request->ip();

        if (RateLimiter::tooManyAttempts($primaryKey, 3) || RateLimiter::tooManyAttempts($secondaryKey, 3)) {
            $seconds = max(RateLimiter::availableIn($primaryKey), RateLimiter::availableIn($secondaryKey));

            return response()->json([
                'ok' => false,
                'error' => [
                    'code' => 'AUTH_TOO_MANY_REQUESTS',
                    'message' => "Please try again in {$seconds} seconds.",
                    'details' => null,
                ],
            ], 429)->withHeaders([
                'Retry-After' => $seconds,
            ]);
        }

        // Send notification via afterCommit if using Transactions for safer operational dispatch
        $user->notify((new VerifyEmailLinkNotification)->afterCommit());
        
        RateLimiter::hit($primaryKey, 600); // 10 minutes decay
        RateLimiter::hit($secondaryKey, 600); 

        AuditLogger::log('auth.email_verification_sent', $user, [
            'user_public_id' => $user->public_id,
            'mode' => 'sent',
            'via' => 'resend',
        ], $user);

        return $this->success(['message' => 'Verification link sent.']);
    }
}
