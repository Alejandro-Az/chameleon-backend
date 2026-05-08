<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Camaleon API",
 *     version="1.0.0",
 *     description="API REST de Camaleon — eventos sociales"
 * )
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
abstract class Controller
{
    //
}
