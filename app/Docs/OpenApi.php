<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\OpenApi]
#[OA\Info(
    version: '1.0.0',
    title: 'Kaan Core Backend API',
    description: 'API base reutilizable (Auth v1 + Sessions enforcement + Admin Users).'
)]
#[OA\Server(url: 'http://127.0.0.1:8000', description: 'Local')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT'
)]
final class OpenApi
{
}
