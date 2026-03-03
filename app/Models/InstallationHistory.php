<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallationHistory extends Model
{
    protected $table = 'installation_histories';

    protected $fillable = [
        'license_id',
        'tool_slug',
        'install_domain',
        'bd_base_url',
        'source',
        'success',
        'details',
        'order_id',
        'customer_id',
    ];

    protected $casts = [
        'success' => 'boolean',
        'details' => 'array',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    /** Tool display name from config when available. */
    public function getToolNameAttribute(): string
    {
        $registry = config("tools.registry.{$this->tool_slug}");
        return $registry['name'] ?? $this->tool_slug;
    }
}
