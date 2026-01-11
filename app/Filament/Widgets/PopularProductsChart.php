<?php

namespace App\Filament\Widgets;

use App\Models\CartEvent;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PopularProductsChart extends ChartWidget
{
    protected ?string $heading = 'Most Added Products (Last 30 Days)';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

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

        $labels = $topProducts->map(function ($item) {
            // Extract product ID from GID
            $parts = explode('/', $item->product_id);
            return 'Product ' . end($parts);
        })->toArray();

        $data = $topProducts->pluck('total_quantity')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Quantity Added',
                    'data' => $data,
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.5)',
                        'rgba(16, 185, 129, 0.5)',
                        'rgba(245, 158, 11, 0.5)',
                        'rgba(239, 68, 68, 0.5)',
                        'rgba(139, 92, 246, 0.5)',
                        'rgba(236, 72, 153, 0.5)',
                        'rgba(20, 184, 166, 0.5)',
                        'rgba(251, 146, 60, 0.5)',
                        'rgba(148, 163, 184, 0.5)',
                        'rgba(14, 165, 233, 0.5)',
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
