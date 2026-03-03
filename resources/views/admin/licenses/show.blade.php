@extends('layouts.admin')

@section('title', 'License')
@section('heading', 'License')

@section('top_actions')
    <a href="{{ route('admin.licenses.index') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">← Back to list</a>
@endsection

@section('content')
<div class="space-y-6 max-w-3xl">
    {{-- Details card --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden animate-slide-up">
        <div class="px-5 py-4 border-b border-slate-200">
            <h2 class="text-base font-semibold text-slate-900">License details</h2>
        </div>
        <div class="p-5">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="font-medium text-slate-500 uppercase tracking-wider text-xs">Token</dt>
                    <dd class="mt-1 flex items-center gap-2 flex-wrap">
                        <span class="font-mono text-slate-800 break-all">{{ $license->token }}</span>
                        <button type="button" class="license-copy-btn inline-flex items-center p-1.5 rounded text-slate-500 hover:bg-slate-200 hover:text-slate-700 focus:ring-2 focus:ring-sky-500 focus:ring-offset-1" data-copy="{{ $license->token }}" title="Copy token" aria-label="Copy token">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </button>
                        <span class="license-copy-msg text-sm font-medium text-emerald-600 opacity-0 transition-opacity duration-200">Copied!</span>
                    </dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500 uppercase tracking-wider text-xs">Tool</dt>
                    <dd class="mt-1 text-slate-800">{{ $license->tool_slug }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500 uppercase tracking-wider text-xs">Valid from</dt>
                    <dd class="mt-1 text-slate-800">{{ $license->getValidFromUtc() ? $license->getValidFromUtc()->format('M j, Y g:i:s A') . ' UTC' : '–' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500 uppercase tracking-wider text-xs">Valid until</dt>
                    <dd class="mt-1 text-slate-800">{{ $license->valid_until ? $license->valid_until->format('M j, Y g:i:s A') . ' UTC' : '–' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500 uppercase tracking-wider text-xs">Domain</dt>
                    <dd class="mt-1 text-slate-800">{{ $license->allowed_domain ?? '–' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500 uppercase tracking-wider text-xs">Status</dt>
                    <dd class="mt-1">
                        @if($license->statusLabel() === 'revoked')
                            <span class="text-slate-400 line-through">Revoked</span>
                        @elseif($license->statusLabel() === 'expired')
                            <span class="inline-flex items-center gap-1.5 text-amber-600 font-medium">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span> Expired
                            </span>
                        @elseif($license->statusLabel() === 'not_yet_valid')
                            <span class="inline-flex items-center gap-1.5 text-slate-500 font-medium">
                                <span class="w-2 h-2 rounded-full bg-slate-400"></span> Not yet valid
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-emerald-600 font-medium">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Active
                            </span>
                        @endif
                    </dd>
                </div>
            </dl>
            <p class="mt-4 pt-4 border-t border-slate-100">
                <a href="{{ route('admin.installation-history.index', ['license_id' => $license->id]) }}" class="text-sm text-sky-600 hover:underline font-medium">View installation history for this license →</a>
            </p>
        </div>
    </div>

    {{-- Edit form --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden animate-slide-up">
        <div class="px-5 py-4 border-b border-slate-200">
            <h2 class="text-base font-semibold text-slate-900">Edit license</h2>
        </div>
        <form method="post" action="{{ route('admin.licenses.update', $license) }}" class="p-5 space-y-4" id="license-edit-form">
            @csrf
            @method('PATCH')
            <input type="hidden" name="timezone" id="license-timezone" value="">
            <div class="mb-4">
                    <label for="license-timezone-select" class="block text-sm font-medium text-slate-700 mb-1.5">Timezone for dates below</label>
                    <select id="license-timezone-select" class="w-full max-w-md px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        <option value="UTC">UTC</option>
                        <option value="America/New_York">America/New_York</option>
                        <option value="America/Chicago">America/Chicago</option>
                        <option value="America/Denver">America/Denver</option>
                        <option value="America/Los_Angeles">America/Los_Angeles</option>
                        <option value="Europe/London">Europe/London</option>
                        <option value="Europe/Paris">Europe/Paris</option>
                        <option value="Europe/Berlin">Europe/Berlin</option>
                        <option value="Asia/Dubai">Asia/Dubai</option>
                        <option value="Asia/Kolkata">Asia/Kolkata</option>
                        <option value="Asia/Singapore">Asia/Singapore</option>
                        <option value="Asia/Tokyo">Asia/Tokyo</option>
                        <option value="Australia/Sydney">Australia/Sydney</option>
                    </select>
                    <p class="text-xs text-slate-500 mt-1">Times you enter are in this timezone; stored as UTC. Server validates in UTC.</p>
                </div>
            <input type="hidden" id="data-valid-from" value="{{ $license->getValidFromUtc() ? $license->getValidFromUtc()->toIso8601String() : '' }}">
            <input type="hidden" id="data-valid-until" value="{{ $license->getValidUntilUtc() ? $license->getValidUntilUtc()->toIso8601String() : '' }}">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="valid_from" class="block text-sm font-medium text-slate-700 mb-1.5">Valid from (optional)</label>
                    <div class="flex gap-2 flex-wrap">
                        <input type="datetime-local" id="valid_from" name="valid_from" step="1" class="flex-1 min-w-0 px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        <button type="button" id="valid_from_now" class="px-3 py-2.5 text-sm font-medium text-slate-600 bg-slate-100 border border-slate-300 rounded-lg hover:bg-slate-200 whitespace-nowrap">Now</button>
                        <button type="button" id="valid_from_clear" class="px-3 py-2.5 text-sm font-medium text-slate-600 bg-slate-100 border border-slate-300 rounded-lg hover:bg-slate-200 whitespace-nowrap">Clear</button>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Leave empty or click Clear for license to be valid immediately.</p>
                </div>
                <div>
                    <label for="valid_until" class="block text-sm font-medium text-slate-700 mb-1.5">Valid until (optional)</label>
                    <div class="flex gap-2">
                        <input type="datetime-local" id="valid_until" name="valid_until" step="1" class="flex-1 min-w-0 px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        <button type="button" id="valid_until_5min" class="px-3 py-2.5 text-sm font-medium text-slate-600 bg-slate-100 border border-slate-300 rounded-lg hover:bg-slate-200 whitespace-nowrap">+5 min</button>
                    </div>
                </div>
            </div>
            <div>
                <label for="allowed_domain" class="block text-sm font-medium text-slate-700 mb-1.5">Allowed domain (optional)</label>
                <input type="text" id="allowed_domain" name="allowed_domain" value="{{ old('allowed_domain', $license->allowed_domain) }}" placeholder="example.com" class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
            </div>
            <div>
                <label for="subscription_id" class="block text-sm font-medium text-slate-700 mb-1.5">Subscription ID (optional)</label>
                <input type="text" id="subscription_id" name="subscription_id" value="{{ old('subscription_id', $license->subscription_id) }}" placeholder="External reference" class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
            </div>
            @if($license->statusLabel() !== 'revoked')
            <div class="flex items-center gap-2">
                <input type="checkbox" id="revoked" name="revoked" value="1" class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                <label for="revoked" class="text-sm text-slate-700">Revoke this license</label>
            </div>
            @endif
            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-sky-500 rounded-lg hover:bg-sky-600 focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">Save changes</button>
                <a href="{{ route('admin.licenses.index') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Cancel</a>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
(function () {
    var tz = Intl && Intl.DateTimeFormat && Intl.DateTimeFormat().resolvedOptions().timeZone;
    var tzEl = document.getElementById('license-timezone');
    var tzSelect = document.getElementById('license-timezone-select');
    if (tzEl && tz) tzEl.value = tz;
    if (tzSelect && tz) {
        var opt = Array.prototype.find.call(tzSelect.options, function (o) { return o.value === tz; });
        if (opt) tzSelect.value = tz; else if (tzSelect.options.length) tzSelect.selectedIndex = 0;
    }
    if (tzSelect && tzEl) {
        tzEl.value = tzSelect.value;
        tzSelect.addEventListener('change', function () { tzEl.value = tzSelect.value; });
    }

    function toLocalISO(d, withSeconds) {
        if (!d || !(d instanceof Date) || isNaN(d)) return '';
        var y = d.getFullYear(), m = String(d.getMonth() + 1).padStart(2, '0'), day = String(d.getDate()).padStart(2, '0');
        var h = String(d.getHours()).padStart(2, '0'), min = String(d.getMinutes()).padStart(2, '0'), sec = String(d.getSeconds()).padStart(2, '0');
        if (withSeconds) return y + '-' + m + '-' + day + 'T' + h + ':' + min + ':' + sec;
        return y + '-' + m + '-' + day + 'T' + h + ':' + min;
    }
    var validFrom = document.getElementById('valid_from');
    var validUntil = document.getElementById('valid_until');
    var fromVal = document.getElementById('data-valid-from') && document.getElementById('data-valid-from').value;
    var untilVal = document.getElementById('data-valid-until') && document.getElementById('data-valid-until').value;
    if (fromVal && validFrom) validFrom.value = toLocalISO(new Date(fromVal), true);
    if (untilVal && validUntil) validUntil.value = toLocalISO(new Date(untilVal), true);
    document.getElementById('valid_from_now').addEventListener('click', function () {
        if (validFrom) validFrom.value = toLocalISO(new Date(), true);
    });
    var validFromClear = document.getElementById('valid_from_clear');
    if (validFromClear && validFrom) validFromClear.addEventListener('click', function () { validFrom.value = ''; });
    document.getElementById('valid_until_5min').addEventListener('click', function () {
        var d = new Date();
        d.setMinutes(d.getMinutes() + 5);
        if (validUntil) validUntil.value = toLocalISO(d, true);
    });
    document.querySelectorAll('.license-copy-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var text = btn.getAttribute('data-copy');
            if (!text) return;
            var msg = btn.parentElement.querySelector('.license-copy-msg');
            navigator.clipboard.writeText(text).then(function () {
                if (msg) { msg.style.opacity = '1'; setTimeout(function () { msg.style.opacity = '0'; }, 2000); }
            });
        });
    });
})();
</script>
@endpush
@endsection
