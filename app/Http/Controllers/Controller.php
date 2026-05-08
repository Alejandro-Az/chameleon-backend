<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Camaleon API",
 *     version="1.0.0",
 *     description="API REST de Camaleon — eventos sociales"
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
