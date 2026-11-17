<?php

namespace Tests\Feature;

use App\Models\AppConfiguration;
use App\Models\Package;
use App\Models\PaymentGateway;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantResetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'owner'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    public function test_reset_removes_tenants_and_all_related_data(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => 'secret',
        ]);
        $superAdmin->assignRole('super_admin');

        $package = Package::create(['name' => 'Pro', 'price' => 99, 'is_active' => true]);

        $tenant = Tenant::create(['name' => 'Acme', 'status' => 'active']);

        $tenantUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => 'secret',
        ]);
        $tenantUser->assignRole('owner');

        AppConfiguration::create(['tenant_id' => $tenant->id, 'config' => ['basic' => ['app_name' => 'Acme']]]);
        PaymentGateway::create(['tenant_id' => $tenant->id, 'gateway_type' => 'tabby', 'is_enabled' => true]);

        DB::table('card_brands')->insert([
            'tenant_id' => $tenant->id, 'name' => 'Visa', 'slug' => 'visa', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('device_tokens')->insert([
            'tenant_id' => $tenant->id, 'push_token' => 'tok-1', 'created_at' => now(), 'updated_at' => now(),
        ]);

        // Non-cascading chain: checkout_session -> payment_transaction -> shopify_orders_mapping
        $sessionId = DB::table('checkout_sessions')->insertGetId([
            'tenant_id' => $tenant->id,
            'session_uuid' => (string) Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
            'amount' => 100.00,
            'currency' => 'AED',
            'customer_email' => 'c@example.com',
            'customer_phone' => '+971500000000',
            'shipping_address' => json_encode(['city' => 'Dubai']),
            'line_items' => json_encode([]),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $txnId = DB::table('payment_transactions')->insertGetId([
            'tenant_id' => $tenant->id,
            'checkout_session_id' => $sessionId,
            'psp_type' => 'tabby',
            'amount' => 100.00,
            'currency' => 'AED',
            'idempotency_key' => (string) Str::uuid(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('shopify_orders_mapping')->insert([
            'tenant_id' => $tenant->id,
            'checkout_session_id' => $sessionId,
            'payment_transaction_id' => $txnId,
            'shopify_order_id' => 'gid://shopify/Order/1',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $summary = app(TenantResetService::class)->reset();

        // Everything tenant-scoped is gone.
        $this->assertSame(0, Tenant::count());
        $this->assertSame(0, User::whereNotNull('tenant_id')->count());
        foreach ([
            'app_configurations', 'payment_gateways', 'card_brands', 'device_tokens',
            'checkout_sessions', 'payment_transactions', 'shopify_orders_mapping',
        ] as $table) {
            $this->assertSame(0, DB::table($table)->count(), "$table should be empty");
        }

        // Super admin + global data preserved.
        $this->assertTrue(User::whereKey($superAdmin->id)->exists());
        $this->assertTrue($superAdmin->fresh()->hasRole('super_admin'));
        $this->assertTrue(Package::whereKey($package->id)->exists());

        // Role pivot for the deleted tenant user is cleaned up.
        $this->assertSame(0, DB::table('model_has_roles')->where('model_id', $tenantUser->id)->count());

        // Reported summary reflects what was removed.
        $this->assertSame(1, $summary['Tenants']);
        $this->assertSame(1, $summary['Tenant users']);
    }

    public function test_counts_reports_zero_when_no_tenants(): void
    {
        $counts = app(TenantResetService::class)->counts();

        $this->assertSame(0, $counts['Tenants']);
        $this->assertSame(0, array_sum($counts));
    }

    public function test_reset_keeps_other_super_admins_and_is_safe_when_empty(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => 'secret',
        ]);
        $superAdmin->assignRole('super_admin');

        $summary = app(TenantResetService::class)->reset();

        $this->assertSame(0, $summary['Tenants']);
        $this->assertTrue(User::whereKey($superAdmin->id)->exists());
    }
}
