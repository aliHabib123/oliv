<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
