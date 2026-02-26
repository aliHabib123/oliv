<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Shopify\ShopifyStorefrontService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProductController extends Controller
{
    public function __construct(
        private ShopifyStorefrontService $shopifyService
    ) {}

    #[OA\Get(
        path: '/v1/products/{productId}/default-variant',
        tags: ['Products'],
        summary: 'Get product default variant',
        security: [['XAppKey' => []]]
    )]
    #[OA\Parameter(
        name: 'productId',
        in: 'path',
        required: true,
        description: 'Product ID (numeric or GID format)',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Product variant details',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'ok', type: 'boolean'),
                new OA\Property(property: 'product_id', type: 'string'),
                new OA\Property(property: 'variant', type: 'object'),
                new OA\Property(property: 'all_variants', type: 'array', items: new OA\Items())
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Product or tenant not found')]
    #[OA\Response(response: 400, description: 'Bad request')]
    public function getDefaultVariant(Request $request, string $productId): JsonResponse
    {
        try {
            $tenant = $request->attributes->get('tenant');

            if (!$tenant) {
                return response()->json(['error' => 'Tenant not resolved'], 404);
            }

            // Convert numeric ID to GID format if needed
            if (!str_starts_with($productId, 'gid://')) {
                $productId = "gid://shopify/Product/{$productId}";
            }

            // Fetch product with first variant
            $product = $this->shopifyService->getProductWithVariants($tenant, $productId);

            if (!$product) {
                return response()->json(['error' => 'Product not found'], 404);
            }

            return response()->json([
                'ok' => true,
                'product_id' => $product['id'],
                'variant' => $product['default_variant'],
                'all_variants' => $product['variants'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
