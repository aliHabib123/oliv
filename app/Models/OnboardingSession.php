<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingSession extends Model
{
    protected $fillable = [
        'tenant_id',
        'platform',
        'step',
        'shopify_domain',
        'imported_data',
        'wizard_payload',
        'ai_recommended',
        'completed_at',
    ];

    protected $casts = [
        'imported_data' => 'array',
        'wizard_payload' => 'array',
        'ai_recommended' => 'array',
        'completed_at' => 'datetime',
        'step' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Determine if the onboarding session has been completed.
     */
    public function isComplete(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * Determine if the imported data is ready for review.
     */
    public function isImportReady(): bool
    {
        return ! empty($this->imported_data) && ! isset($this->imported_data['error']);
    }
}
