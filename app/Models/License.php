<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class License extends Model
{
    protected $fillable = [
        'subscription_id',
        'token',
        'valid_from',
        'valid_until',
        'allowed_domain',
        'tool_slug',
        'revoked',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'revoked' => 'boolean',
    ];

    /** Interpret stored valid_from as UTC (DB stores UTC from controller). */
    public function getValidFromUtc(): ?Carbon
    {
        $raw = $this->getRawOriginal('valid_from');
        return $raw !== null ? Carbon::parse($raw, 'UTC') : null;
    }

    /** Interpret stored valid_until as UTC (DB stores UTC from controller). */
    public function getValidUntilUtc(): ?Carbon
    {
        $raw = $this->getRawOriginal('valid_until');
        return $raw !== null ? Carbon::parse($raw, 'UTC') : null;
    }

    public function isValid(?string $domain = null): bool
    {
        return $this->getInvalidReason($domain) === null;
    }

    /**
     * Status for display in admin (ignores domain).
     * Returns: 'revoked' | 'expired' | 'not_yet_valid' | 'active'
     */
    public function statusLabel(): string
    {
        if ($this->revoked) {
            return 'revoked';
        }
        $fromUtc = $this->getValidFromUtc();
        if ($fromUtc && $fromUtc->isFuture()) {
            return 'not_yet_valid';
        }
        $untilUtc = $this->getValidUntilUtc();
        if ($untilUtc && $untilUtc->isPast()) {
            return 'expired';
        }
        return 'active';
    }

    /**
     * Return the reason the license is invalid, or null if valid.
     */
    public function getInvalidReason(?string $domain = null): ?string
    {
        if ($this->revoked) {
            return 'License has been revoked.';
        }
        $fromUtc = $this->getValidFromUtc();
            if ($fromUtc && $fromUtc->isFuture()) {
            return 'License is not yet valid (valid from ' . $fromUtc->format('M j, Y g:i:s A') . ' UTC). Leave “Valid from” empty or set to now for immediate use.';
            }
        $untilUtc = $this->getValidUntilUtc();
        if ($untilUtc && $untilUtc->isPast()) {
            return 'License has expired (expired ' . $untilUtc->format('M j, Y g:i:s A') . ' UTC).';
        }
        if ($this->allowed_domain !== null && $domain !== null && $this->allowed_domain !== $domain) {
            return 'License is not valid for this domain (allowed: ' . $this->allowed_domain . ').';
        }
        return null;
    }

    public function installationHistories(): HasMany
    {
        return $this->hasMany(InstallationHistory::class);
    }
}
