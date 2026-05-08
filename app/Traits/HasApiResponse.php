<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait HasApiResponse
{
    /**
     * Return a standardized success response.
     *
     * @param mixed $data Content to return in 'data' key
     * @param int $code HTTP Status code (default 200)
     * @return JsonResponse
     */
    protected function success(mixed $data, int $code = 200): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => $data,
        ], $code);
    }

    /**
     * Return a standardized error response.
     *
     * @param string $code Internal error code (e.g. 'AUTH_INVALID')
     * @param string $message Human readable message
     * @param int $statusCode HTTP Status code (default 400)
     * @param mixed|null $details Optional array/object with error details
     * @return JsonResponse
     */
    protected function error(string $code, string $message, int $statusCode = 400, mixed $details = null): JsonResponse
    {
        // Contract: `details` siempre presente (nullable) para evitar branching en clientes
        $response = [
            'code' => $code,
            'message' => $message,
            'details' => $details,
        ];

        return response()->json([
            'ok' => false,
            'error' => $response,
        ], $statusCode);
    }
}
