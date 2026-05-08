<?php

namespace App\Docs\V1\Auth;

use OpenApi\Attributes as OA;

class AuthEmailVerificationEndpoints
{
    #[OA\Post(
        path: '/api/v1/auth/email/verification-notification',
        summary: 'Resend Verification Email',
        description: 'Sends a new email verification link to the authenticated user. Rate limited to 3 attempts every 10 minutes.',
        operationId: 'resendVerificationEmail',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Verification link sent successfully or already verified.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'message', type: 'string', example: 'Verification link sent.'),
                                new OA\Property(property: 'verified', type: 'boolean', example: true, description: 'Present when already verified'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 429,
                description: 'Too Many Requests — rate limited.',
                headers: [
                    new OA\Header(header: 'Retry-After', description: 'Seconds until next attempt allowed.', schema: new OA\Schema(type: 'integer')),
                ],
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function resendVerificationEmail() {}

    #[OA\Get(
        path: '/api/v1/auth/verify-email/{id}/{hash}',
        summary: 'Verify Email Address',
        description: 'Verifies the user email via a signed URL. Returns a JSON envelope if Accept headers request it, or redirects to the frontend configured URL with a verified=1 or verified=0 query parameter.',
        operationId: 'verifyEmail',
        tags: ['Authentication'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'User public ULID', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'hash', description: 'SHA1 hash of the user email', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'expires', description: 'Signature expiration timestamp', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'signature', description: 'URL Signature', in: 'query', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Email verified successfully (JSON Mode).',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data', 
                            type: 'object', 
                            properties: [
                                new OA\Property(property: 'verified', type: 'boolean', example: true)
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 302,
                description: 'Browser Redirect to configured frontend URL.'
            ),
            new OA\Response(
                response: 403,
                description: 'Invalid or Expired Signature (JSON Mode). error.code = AUTH_VERIFICATION_INVALID.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
    public function verifyEmail() {}

    #[OA\Get(
        path: '/api/v1/auth/email/verification-status',
        summary: 'Get Verification Status',
        description: 'Returns whether the currently authenticated user has a verified email address.',
        operationId: 'getVerificationStatus',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Status retrieved successfully.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data', 
                            type: 'object', 
                            properties: [
                                new OA\Property(property: 'verified', type: 'boolean', example: true)
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function getVerificationStatus() {}
}
