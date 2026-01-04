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
        'status',
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
}
