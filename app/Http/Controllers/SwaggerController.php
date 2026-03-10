<?php

namespace App\Http\Controllers;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         title="Oliv API",
 *         version="1.0.0",
 *         description="Oliv API Documentation"
 *     ),
 *     @OA\Server(
 *         url="/api",
 *         description="API Server"
 *     )
 * )
 * @OA\SecurityScheme(
 *     securityScheme="AppKey",
 *     type="apiKey",
 *     in="header",
 *     name="X-App-Key",
 *     description="App Key for tenant authentication"
 * )
 */
class SwaggerController extends Controller
{
    //
}