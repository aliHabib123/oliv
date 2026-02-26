<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Oliv API',
    version: '1.0.0',
    description: 'API for Oliv mobile app'
)]
#[OA\Server(
    url: '/api',
    description: 'API Base URL'
)]
#[OA\SecurityScheme(
    securityScheme: 'XAppKey',
    type: 'apiKey',
    name: 'X-App-Key',
    in: 'header',
    description: 'Tenant public key (generated during registration)'
)]
class OpenApiController
{
}
