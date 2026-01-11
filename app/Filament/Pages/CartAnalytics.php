<?php

namespace App\Filament\Pages;

use App\Models\Cart;
use App\Models\CartEvent;
use App\Models\CheckoutEvent;
use App\Models\Tenant;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Contracts\Auth\Authenticatable;
use UnitEnum;

class CartAnalytics extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string | UnitEnum | null $navigationGroup = 'Analytics';

    protected static ?string $navigationLabel = 'Cart & Checkout';

    protected static ?string $title = 'Cart & Checkout Analytics';

    protected static ?int $navigationSort = 10;

    public ?Tenant $tenant = null;

    public function mount(): void
    {
        /** @var Authenticatable|null $user */
        $user = auth()->user();

        if (! $user || ! method_exists($user, 'hasAnyRole')) {
            abort(403);
        }

        if ($user->hasRole('super_admin')) {
            $this->redirect(route('filament.admin.resources.tenants.index'));

            return;
        }

        $this->tenant = $this->resolveTenant();
    }

    public static function canAccess(): bool
    {
        /** @var Authenticatable|null $user */
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole(['super_admin', 'owner', 'admin']);
        }

        return false;
    }

    protected function resolveTenant(): Tenant
    {
        /** @var Authenticatable $user */
        $user = auth()->user();

        if (! $user || ! method_exists($user, 'tenant')) {
            abort(403, 'User does not belong to a tenant.');
        }

        /** @var Tenant|null $tenant */
        $tenant = $user->tenant;

        if (! $tenant) {
            abort(403, 'User does not belong to a tenant.');
        }

        return $tenant;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\CartStatsOverview::class,
            \App\Filament\Widgets\PopularProductsChart::class,
            \App\Filament\Widgets\CartEventsChart::class,
            \App\Filament\Widgets\RecentCheckoutsTable::class,
        ];
    }
}
