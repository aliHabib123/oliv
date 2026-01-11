<?php

namespace App\Filament\Widgets;

use App\Models\CartEvent;
use App\Models\CheckoutEvent;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class CartStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        if (! $user || ! method_exists($user, 'tenant')) {
            return [];
        }

        $tenant = $user->tenant;

        if (! $tenant) {
            return [];
        }

        // Total items added to cart (last 30 days)
        $totalItemsAdded = CartEvent::where('tenant_id', $tenant->id)
            ->where('event_type', 'add_to_cart')
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('quantity');

        // Total checkouts created (last 30 days)
        $totalCheckouts = CheckoutEvent::where('tenant_id', $tenant->id)
            ->where('event_type', 'checkout_created')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        // Total completed orders (last 30 days)
        $completedOrders = CheckoutEvent::where('tenant_id', $tenant->id)
            ->where('event_type', 'order_completed')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        // Conversion rate
        $conversionRate = $totalCheckouts > 0
            ? round(($completedOrders / $totalCheckouts) * 100, 1)
            : 0;

        // Total order value (last 30 days)
        $totalOrderValue = CheckoutEvent::where('tenant_id', $tenant->id)
            ->where('event_type', 'order_completed')
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('total_price');

        return [
            Stat::make('Items Added to Cart', $totalItemsAdded)
                ->description('Last 30 days')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('success'),

            Stat::make('Checkouts Created', $totalCheckouts)
                ->description('Last 30 days')
                ->descriptionIcon('heroicon-m-cursor-arrow-rays')
                ->color('info'),

            Stat::make('Orders Completed', $completedOrders)
                ->description("Conversion rate: {$conversionRate}%")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Total Order Value', '$' . number_format($totalOrderValue, 2))
                ->description('Last 30 days')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning'),
        ];
    }
}
