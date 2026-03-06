<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Oliv API",
 *     version="1.0.0",
 *     description="API documentation for Oliv mobile app backend"
 * )
 * @OA\Server(
 *     url="/api",
 *     description="API Server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="AppKey",
 *     type="apiKey",
 *     in="header",
 *     name="X-App-Key"
 * )
 */
abstract class Controller
{
}
