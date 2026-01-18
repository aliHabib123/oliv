<?php

namespace App\Filament\Pages;

use App\Models\Addon;
use App\Models\TenantAddon;
use BackedEnum;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class ManageAddons extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationLabel = 'Manage Add-ons';

    protected static ?string $title = 'Manage Add-ons';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.manage-addons';

    public function getViewData(): array
    {
        $tenant = Auth::user()->tenant;

        if (!$tenant) {
            return [
                'addons' => [],
                'subscribedAddons' => [],
            ];
        }

        $addons = Addon::where('is_active', true)
            ->orderBy('name')
            ->get();

        $subscribedAddons = TenantAddon::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->pluck('addon_id')
            ->toArray();

        return [
            'addons' => $addons,
            'subscribedAddons' => $subscribedAddons,
        ];
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        // Only accessible by tenant owners and admins, not superadmins
        if (!$user || !$user->tenant_id) {
            return false;
        }

        return $user->hasAnyRole(['owner', 'admin']);
    }

    public function subscribe(int $addonId): void
    {
        $tenant = Auth::user()->tenant;

        if (!$tenant) {
            Notification::make()
                ->danger()
                ->title('No tenant associated with your account')
                ->send();

            return;
        }

        $addon = Addon::find($addonId);

        if (!$addon) {
            Notification::make()
                ->danger()
                ->title('Add-on not found')
                ->send();

            return;
        }

        // Check if already subscribed
        $existing = TenantAddon::where('tenant_id', $tenant->id)
            ->where('addon_id', $addon->id)
            ->where('status', 'active')
            ->first();

        if ($existing) {
            Notification::make()
                ->warning()
                ->title('Already subscribed')
                ->body("You are already subscribed to {$addon->name}")
                ->send();

            return;
        }

        // Create subscription (dummy payment)
        TenantAddon::create([
            'tenant_id' => $tenant->id,
            'addon_id' => $addon->id,
            'status' => 'active',
            'subscribed_at' => now(),
        ]);

        Notification::make()
            ->success()
            ->title('Successfully subscribed')
            ->body("You are now subscribed to {$addon->name}")
            ->send();
    }

    public function unsubscribe(int $addonId): void
    {
        $tenant = Auth::user()->tenant;

        if (!$tenant) {
            Notification::make()
                ->danger()
                ->title('No tenant associated with your account')
                ->send();

            return;
        }

        $addon = Addon::find($addonId);

        if (!$addon) {
            Notification::make()
                ->danger()
                ->title('Add-on not found')
                ->send();

            return;
        }

        TenantAddon::where('tenant_id', $tenant->id)
            ->where('addon_id', $addon->id)
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);

        Notification::make()
            ->success()
            ->title('Successfully unsubscribed')
            ->body("You have unsubscribed from {$addon->name}")
            ->send();
    }
}
