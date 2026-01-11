<?php

namespace App\Filament\Widgets;

use App\Models\CartEvent;
use App\Models\CheckoutEvent;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class CartEventsChart extends ChartWidget
{
    protected ?string $heading = 'Cart Activity (Last 7 Days)';

    protected static ?int $sort = 3;

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

        // Get last 7 days
        $days = collect(range(6, 0))->map(fn ($day) => now()->subDays($day));

        $labels = $days->map(fn ($date) => $date->format('M d'))->toArray();

        // Cart adds per day
        $cartAdds = $days->map(function ($date) use ($tenant) {
            return CartEvent::where('tenant_id', $tenant->id)
                ->where('event_type', 'add_to_cart')
                ->whereDate('created_at', $date->format('Y-m-d'))
                ->count();
        })->toArray();

        // Checkouts per day
        $checkouts = $days->map(function ($date) use ($tenant) {
            return CheckoutEvent::where('tenant_id', $tenant->id)
                ->where('event_type', 'checkout_created')
                ->whereDate('created_at', $date->format('Y-m-d'))
                ->count();
        })->toArray();

        // Orders per day
        $orders = $days->map(function ($date) use ($tenant) {
            return CheckoutEvent::where('tenant_id', $tenant->id)
                ->where('event_type', 'order_completed')
                ->whereDate('created_at', $date->format('Y-m-d'))
                ->count();
        })->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Cart Adds',
                    'data' => $cartAdds,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ],
                [
                    'label' => 'Checkouts',
                    'data' => $checkouts,
                    'borderColor' => 'rgb(245, 158, 11)',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                ],
                [
                    'label' => 'Orders',
                    'data' => $orders,
                    'borderColor' => 'rgb(16, 185, 129)',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
