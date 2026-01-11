<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Shopify\ShopifyStorefrontService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private ShopifyStorefrontService $shopifyService
    ) {}

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
