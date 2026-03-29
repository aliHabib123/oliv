<?php

namespace App\Filament\Widgets;

use App\Models\CartEvent;
use App\Services\Shopify\ShopifyStorefrontService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PopularProductsChart extends ChartWidget
{
    protected ?string $heading = 'Most Added Products (Last 30 Days)';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected ?string $maxHeight = '400px';

    protected function getData(): array
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        if (! $user || ! method_exists($user, 'tenant')) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $tenant = $user->tenant;

        if (! $tenant) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        // Get top 10 products by quantity added
        $topProducts = CartEvent::where('tenant_id', $tenant->id)
            ->where('event_type', 'add_to_cart')
            ->where('created_at', '>=', now()->subDays(30))
            ->select('product_id', DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();

        // Fetch product titles from Shopify (with caching)
        $shopifyService = app(ShopifyStorefrontService::class);
        $labels = $topProducts->map(function ($item) use ($tenant, $shopifyService) {
            $productId = $item->product_id;

            // Cache product title for 1 hour
            return Cache::remember("product_title_{$tenant->id}_{$productId}", 3600, function () use ($tenant, $shopifyService, $productId) {
                $product = $shopifyService->getProductById($tenant, $productId);
                if ($product && !empty($product['title'])) {
                    // Truncate for display, full title shown in tooltip
                    $title = $product['title'];
                    return strlen($title) > 30 ? substr($title, 0, 27) . '...' : $title;
                }
                // Fallback to short ID
                $parts = explode('/', $productId);
                return 'Product #' . substr(end($parts), -6);
            });
        })->toArray();

        $data = $topProducts->pluck('total_quantity')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Quantity Added',
                    'data' => $data,
                    'backgroundColor' => 'rgba(132, 117, 78, 0.7)',
                    'borderColor' => 'rgb(132, 117, 78)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'barThickness' => 25,
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
                'y' => [
                    'ticks' => [
                        'autoSkip' => false,
                        'font' => [
                            'size' => 12,
                        ],
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'enabled' => true,
                    'callbacks' => [
                        'title' => null,
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
