<?php

namespace App\Filament\Pages;

use App\Models\Addon;
use App\Models\Subscription;
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
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\SubscriptionItem;

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

        // Get active subscription
        $subscription = Subscription::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->first();

        if (!$subscription || !$subscription->stripe_subscription_id) {
            Notification::make()
                ->danger()
                ->title('No active subscription')
                ->body('You need an active subscription to add addons')
                ->send();

            return;
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            // Calculate price based on billing period
            $interval = $subscription->billing_period === 'yearly' ? 'year' : 'month';
            $unitAmount = $subscription->billing_period === 'yearly'
                ? $addon->price * 12 * 100
                : $addon->price * 100;

            // Create price in Stripe
            $price = \Stripe\Price::create([
                'currency' => config('services.stripe.currency', 'aed'),
                'unit_amount' => $unitAmount,
                'recurring' => ['interval' => $interval],
                'product_data' => [
                    'name' => $addon->name,
                ],
            ]);

            // Add item to Stripe subscription with prorated billing
            $subscriptionItem = SubscriptionItem::create([
                'subscription' => $subscription->stripe_subscription_id,
                'price' => $price->id,
                'proration_behavior' => 'create_prorations', // Charge prorated amount immediately
            ]);

            // Create local record
            TenantAddon::create([
                'tenant_id' => $tenant->id,
                'addon_id' => $addon->id,
                'stripe_subscription_item_id' => $subscriptionItem->id,
                'status' => 'active',
                'subscribed_at' => now(),
            ]);

            Log::info('Addon subscribed via Stripe', [
                'tenant_id' => $tenant->id,
                'addon_id' => $addon->id,
                'stripe_item_id' => $subscriptionItem->id,
            ]);

            Notification::make()
                ->success()
                ->title('Successfully subscribed')
                ->body("You have been charged a prorated amount for {$addon->name}")
                ->send();
        } catch (\Exception $e) {
            Log::error('Addon subscription failed', [
                'error' => $e->getMessage(),
                'addon_id' => $addon->id,
                'tenant_id' => $tenant->id,
            ]);

            Notification::make()
                ->danger()
                ->title('Subscription failed')
                ->body('Failed to process payment. Please try again.')
                ->send();
        }
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

        $tenantAddon = TenantAddon::where('tenant_id', $tenant->id)
            ->where('addon_id', $addon->id)
            ->where('status', 'active')
            ->first();

        if (!$tenantAddon) {
            Notification::make()
                ->warning()
                ->title('Not subscribed')
                ->body("You are not subscribed to {$addon->name}")
                ->send();

            return;
        }

        try {
            // If there's a Stripe subscription item, delete it
            if ($tenantAddon->stripe_subscription_item_id) {
                Stripe::setApiKey(config('services.stripe.secret'));

                // Delete from Stripe (no refund, stays active until period end)
                SubscriptionItem::delete($tenantAddon->stripe_subscription_item_id, [
                    'proration_behavior' => 'none', // No refund
                ]);

                Log::info('Addon unsubscribed via Stripe', [
                    'tenant_id' => $tenant->id,
                    'addon_id' => $addon->id,
                    'stripe_item_id' => $tenantAddon->stripe_subscription_item_id,
                ]);
            }

            // Update local status to cancelled
            $tenantAddon->update(['status' => 'cancelled']);

            Notification::make()
                ->success()
                ->title('Successfully unsubscribed')
                ->body("The addon will remain active until your subscription period ends")
                ->send();
        } catch (\Exception $e) {
            Log::error('Addon unsubscribe failed', [
                'error' => $e->getMessage(),
                'addon_id' => $addon->id,
                'tenant_id' => $tenant->id,
            ]);

            Notification::make()
                ->danger()
                ->title('Unsubscribe failed')
                ->body('Failed to cancel addon. Please try again.')
                ->send();
        }
    }
}
