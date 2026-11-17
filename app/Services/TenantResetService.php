<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Wipes all tenants and their related data so the platform can start fresh.
 *
 * Super-admin users (which have a null tenant_id) and global catalogue data
 * (packages, addons, countries, pricing, roles) are preserved.
 */
class TenantResetService
{
    /**
     * Tables that carry a tenant_id and are NOT removed by the tenants cascade
     * (they were created with constrained() and no cascadeOnDelete), so they must
     * be deleted explicitly first — child-most table first.
     *
     * @var list<string>
     */
    private const NON_CASCADING_CHILDREN = [
        'shopify_orders_mapping',
        'payment_transactions',
        'checkout_sessions',
    ];

    /**
     * Tenant-scoped tables removed automatically via the tenants FK cascade.
     * Listed here only so we can report counts before deletion.
     *
     * @var list<string>
     */
    private const CASCADING_CHILDREN = [
        'subscriptions',
        'tenant_addons',
        'payment_gateways',
        'app_configurations',
        'home_layouts',
        'card_brands',
        'device_tokens',
        'carts',
        'cart_events',
        'checkout_events',
    ];

    /**
     * Counts of everything that a reset would remove, keyed by a human label.
     *
     * @return array<string, int>
     */
    public function counts(): array
    {
        $counts = [
            'Tenants' => Tenant::count(),
            'Tenant users' => User::whereNotNull('tenant_id')->count(),
        ];

        foreach ([...self::NON_CASCADING_CHILDREN, ...self::CASCADING_CHILDREN] as $table) {
            $counts[$this->label($table)] = DB::table($table)->count();
        }

        return $counts;
    }

    /**
     * Delete every tenant and all related data inside one transaction.
     * Returns the counts that were removed.
     *
     * @return array<string, int>
     */
    public function reset(): array
    {
        return DB::transaction(function (): array {
            $summary = $this->counts();

            // 1. Children with restrictive foreign keys (child-most first).
            foreach (self::NON_CASCADING_CHILDREN as $table) {
                DB::table($table)->delete();
            }

            // 2. Tenant users. Super admins have a null tenant_id and are kept.
            //    Clean their role pivots first (no FK there to do it for us).
            $tenantUserIds = User::whereNotNull('tenant_id')->pluck('id')->all();

            if (! empty($tenantUserIds)) {
                DB::table('model_has_roles')
                    ->where('model_type', (new User)->getMorphClass())
                    ->whereIn('model_id', $tenantUserIds)
                    ->delete();

                User::whereNotNull('tenant_id')->delete();
            }

            // 3. Tenants — cascades every table in CASCADING_CHILDREN.
            Tenant::query()->delete();

            return $summary;
        });
    }

    private function label(string $table): string
    {
        return ucfirst(str_replace('_', ' ', $table));
    }
}
