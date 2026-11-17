<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'app_key',
        'shopify_shop_domain',
        'shopify_storefront_token',
        'shopify_admin_token',
        'shopify_client_id',
        'shopify_client_secret',
        'shopify_admin_token_expires_at',
        'shopify_admin_refresh_token',
        'status',
        'home_layout',
    ];

    protected $casts = [
        'home_layout' => 'array',
        'intro_config' => 'array',
        'shopify_admin_token_expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant): void {
            if (empty($tenant->app_key)) {
                do {
                    $appKey = Str::uuid()->toString();
                } while (static::where('app_key', $appKey)->exists());

                $tenant->app_key = $appKey;
            }
        });
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function homeLayouts()
    {
        return $this->hasMany(HomeLayout::class);
    }

    public function paymentGateways()
    {
        return $this->hasMany(PaymentGateway::class);
    }
}
