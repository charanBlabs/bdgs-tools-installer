@extends('layouts.admin')

@section('title', 'Add License')
@section('heading', 'Add license')

@section('content')
<div class="bg-white rounded-xl border border-slate-200 shadow-sm max-w-2xl animate-slide-up">
    <div class="px-5 py-4 border-b border-slate-200">
        <h2 class="text-base font-semibold text-slate-900">New license</h2>
    </div>
    <form method="post" action="{{ route('admin.licenses.store') }}" class="p-5 space-y-4" id="license-form">
        @csrf
        <input type="hidden" name="timezone" id="license-timezone" value="">
        <div>
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
            <p class="text-xs text-slate-500 mt-1">Times you enter are in this timezone; stored as UTC.</p>
        </div>
        <div>
            <label for="tool_slug" class="block text-sm font-medium text-slate-700 mb-1.5">Tool</label>
            <select id="tool_slug" name="tool_slug" required class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-shadow">
                @foreach($tools as $slug)
                    <option value="{{ $slug }}">{{ $slug }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="subscription_id" class="block text-sm font-medium text-slate-700 mb-1.5">Subscription ID (optional)</label>
            <input type="text" id="subscription_id" name="subscription_id" placeholder="External subscription reference" class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-shadow">
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="valid_from" class="block text-sm font-medium text-slate-700 mb-1.5">Valid from (optional)</label>
                <div class="flex gap-2">
                    <input type="datetime-local" id="valid_from" name="valid_from" step="1" class="flex-1 min-w-0 px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-shadow">
                    <button type="button" id="valid_from_now" class="px-3 py-2.5 text-sm font-medium text-slate-600 bg-slate-100 border border-slate-300 rounded-lg hover:bg-slate-200 whitespace-nowrap">Now</button>
                </div>
                <p class="mt-1 text-xs text-slate-500"><strong>Leave empty</strong> for valid immediately (recommended). Only set a date if you want the license to start later.</p>
            </div>
            <div>
                <label for="valid_until" class="block text-sm font-medium text-slate-700 mb-1.5">Valid until (optional)</label>
                <div class="flex gap-2">
                    <input type="datetime-local" id="valid_until" name="valid_until" step="1" class="flex-1 min-w-0 px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-shadow">
                    <button type="button" id="valid_until_5min" class="px-3 py-2.5 text-sm font-medium text-slate-600 bg-slate-100 border border-slate-300 rounded-lg hover:bg-slate-200 whitespace-nowrap">+5 min</button>
                </div>
                <p class="mt-1 text-xs text-slate-500">Times are in your local time. For a short test, use “+5 min” then submit quickly.</p>
            </div>
        </div>
        <div>
            <label for="allowed_domain" class="block text-sm font-medium text-slate-700 mb-1.5">Allowed domain (optional)</label>
            <input type="text" id="allowed_domain" name="allowed_domain" placeholder="example.com" class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-shadow">
        </div>
        <div class="flex flex-wrap gap-3 pt-2">
            <button type="submit" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-sky-500 rounded-lg hover:bg-sky-600 focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transition-all shadow-sm">Create license</button>
            <a href="{{ route('admin.licenses.index') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transition-colors">Cancel</a>
        </div>
    </form>
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
    var validFromEl = document.getElementById('valid_from');
    document.getElementById('valid_from_now').addEventListener('click', function () {
        if (validFromEl) validFromEl.value = toLocalISO(new Date(), true);
    });
    document.getElementById('valid_until_5min').addEventListener('click', function () {
        var d = new Date();
        d.setMinutes(d.getMinutes() + 5);
        var until = document.getElementById('valid_until');
        if (until) until.value = toLocalISO(d, true);
    });
})();
</script>
@endpush
@endsection
