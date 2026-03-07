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
        'license_type',
        'revoked',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'revoked' => 'boolean',
    ];

    /**
     * Check if this is a lifetime license (no expiration).
     * Returns false for null/unset license_type for backward compatibility.
     */
    public function isLifetime(): bool
    {
        return $this->license_type === 'lifetime';
    }

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
     * Returns: 'revoked' | 'expired' | 'not_yet_valid' | 'active' | 'lifetime'
     */
    public function statusLabel(): string
    {
        if ($this->revoked) {
            return 'revoked';
        }
        // Lifetime licenses don't expire
        if ($this->isLifetime()) {
            return 'lifetime';
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
        // Lifetime licenses are always valid (if not revoked), only check domain
        if ($this->isLifetime()) {
            if ($this->allowed_domain !== null && $domain !== null && $this->allowed_domain !== $domain) {
                return 'License is not valid for this domain (allowed: ' . $this->allowed_domain . ').';
            }
            return null;
        }
        // Subscription licenses: check expiration dates
        $fromUtc = $this->getValidFromUtc();
        if ($fromUtc && $fromUtc->isFuture()) {
            return 'License is not yet valid (valid from ' . $fromUtc->format('M j, Y g:i:s A') . ' UTC). Leave "Valid from" empty or set to now for immediate use.';
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
