<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppConfiguration extends Model
{
    protected $fillable = [
        'tenant_id',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function getBasicSettings(): array
    {
        return $this->config['basic'] ?? [];
    }

    public function getContactSettings(): array
    {
        return $this->config['contact'] ?? [];
    }

    public function getSocialMediaSettings(): array
    {
        return $this->config['social_media'] ?? [];
    }

    public function getFeatureSettings(): array
    {
        return $this->config['features'] ?? [];
    }

    public function getPolicySettings(): array
    {
        return $this->config['policies'] ?? [];
    }
}
