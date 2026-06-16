<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppConfig extends Model
{
    protected $table = 'app_configs';

    protected $fillable = [
        'tenant_id',
        'app_name',
        'app_icon_url',
        'splash_url',
        'primary_color',
        'secondary_color',
        'font_family',
        'theme_id',
        'home_layout',
        'navigation',
        'publish_status',
        'bundle_id_ios',
        'bundle_id_android',
    ];

    protected $casts = [
        'home_layout' => 'array',
        'navigation' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(AppTheme::class, 'theme_id');
    }
}
