@extends('layouts.admin')

@section('title', 'Licenses')
@section('heading', 'Licenses')

@section('top_actions')
    <a href="{{ route('admin.licenses.create') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-sky-500 rounded-lg hover:bg-sky-600 focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transition-all shadow-sm">Add license</a>
@endsection

@section('content')
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden animate-slide-up mb-4">
    <div class="px-5 py-4 border-b border-slate-200">
        <form method="get" action="{{ route('admin.licenses.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="filter_tool" class="block text-xs font-medium text-slate-500 mb-1">Tool</label>
                <select id="filter_tool" name="tool" class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 bg-white min-w-[140px]">
                    <option value="">All tools</option>
                    @foreach($tools as $slug => $config)
                        <option value="{{ $slug }}" {{ request('tool') === $slug ? 'selected' : '' }}>{{ $config['name'] ?? $slug }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter_status" class="block text-xs font-medium text-slate-500 mb-1">Status</label>
                <select id="filter_status" name="status" class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 bg-white min-w-[140px]">
                    <option value="">All</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="revoked" {{ request('status') === 'revoked' ? 'selected' : '' }}>Revoked</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                    <option value="not_yet_valid" {{ request('status') === 'not_yet_valid' ? 'selected' : '' }}>Not yet valid</option>
                </select>
            </div>
            <div>
                <label for="filter_search" class="block text-xs font-medium text-slate-500 mb-1">Search</label>
                <input type="text" id="filter_search" name="search" value="{{ request('search') }}" placeholder="Token, domain, subscription ID" class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 min-w-[200px]">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-sky-500 rounded-lg hover:bg-sky-600 focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">Filter</button>
                <a href="{{ route('admin.licenses.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Clear</a>
            </div>
        </form>
    </div>
</div>
@if(request()->hasAny(['tool', 'status', 'search']))
    <p class="text-sm text-slate-600 mb-2">
        @if($licenses->total() > 0)
            {{ $licenses->total() }} {{ Str::plural('license', $licenses->total()) }} match the filter.
        @else
            No licenses match the current filter.
        @endif
    </p>
@endif

{{-- Bulk action bar (hidden until selection) --}}
<div id="bulk-action-bar" class="hidden mb-3 flex items-center gap-3 px-4 py-3 bg-slate-800 text-white rounded-xl shadow-sm animate-slide-up">
    <span id="bulk-count-label" class="text-sm font-medium mr-2">0 selected</span>

    {{-- Bulk Revoke --}}
    <form id="bulk-revoke-form" method="post" action="{{ route('admin.licenses.bulk-revoke') }}" onsubmit="return confirmBulkAction(this, 'revoke')">
        @csrf
        <div id="bulk-revoke-ids"></div>
        <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-amber-900 bg-amber-300 rounded-lg hover:bg-amber-400 focus:ring-2 focus:ring-amber-400 focus:ring-offset-1 transition-colors">
            Revoke selected
        </button>
    </form>

    {{-- Bulk Delete --}}
    <form id="bulk-delete-form" method="post" action="{{ route('admin.licenses.bulk-destroy') }}" onsubmit="return confirmBulkAction(this, 'permanently delete')">
        @csrf
        @method('DELETE')
        <div id="bulk-delete-ids"></div>
        <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-1 transition-colors">
            Delete selected
        </button>
    </form>

    <button type="button" id="bulk-deselect-btn" class="ml-auto text-xs text-slate-300 hover:text-white underline">Deselect all</button>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden animate-slide-up">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50/80">
                    <th class="py-3.5 px-4 w-10">
                        <input type="checkbox" id="select-all-checkbox" class="w-4 h-4 rounded border-slate-300 text-sky-500 focus:ring-sky-500 cursor-pointer" title="Select all on this page">
                    </th>
                    <th class="text-left py-3.5 px-4 font-semibold text-slate-600 uppercase tracking-wider">Token</th>
                    <th class="text-left py-3.5 px-4 font-semibold text-slate-600 uppercase tracking-wider">Tool</th>
                    <th class="text-left py-3.5 px-4 font-semibold text-slate-600 uppercase tracking-wider">Type</th>
                    <th class="text-left py-3.5 px-4 font-semibold text-slate-600 uppercase tracking-wider">Valid from</th>
                    <th class="text-left py-3.5 px-4 font-semibold text-slate-600 uppercase tracking-wider">Valid until</th>
                    <th class="text-left py-3.5 px-4 font-semibold text-slate-600 uppercase tracking-wider">Domain</th>
                    <th class="text-left py-3.5 px-4 font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                    <th class="text-left py-3.5 px-4 font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($licenses as $license)
                    <tr class="license-row border-b border-slate-100 hover:bg-slate-50/50 transition-colors cursor-pointer" data-href="{{ route('admin.licenses.show', $license) }}">
                        <td class="py-3 px-4 license-row-actions" onclick="event.stopPropagation()">
                            <input type="checkbox" class="license-checkbox w-4 h-4 rounded border-slate-300 text-sky-500 focus:ring-sky-500 cursor-pointer" value="{{ $license->id }}" data-status="{{ $license->statusLabel() }}">
                        </td>
                        <td class="py-3 px-4">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="font-mono text-xs text-slate-700 break-all">{{ Str::limit($license->token, 28) }}</span>
                                <button type="button" class="license-copy-btn inline-flex items-center p-1 rounded text-slate-500 hover:bg-slate-200 hover:text-slate-700 focus:ring-2 focus:ring-sky-500 focus:ring-offset-1" data-copy="{{ $license->token }}" title="Copy token" aria-label="Copy token">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                </button>
                                <span class="license-copy-msg text-xs font-medium text-emerald-600 whitespace-nowrap opacity-0 transition-opacity duration-200">Copied!</span>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-slate-700">{{ $license->tool_slug }}</td>
                        <td class="py-3 px-4">
                            @if($license->license_type === 'lifetime')
                                <span class="inline-flex items-center gap-1 text-emerald-600 font-medium">
                                    Lifetime
                                </span>
                            @else
                                <span class="text-slate-600">Subscription</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-slate-600">{{ $license->getValidFromUtc() ? $license->getValidFromUtc()->format('Y-m-d H:i:s') . ' UTC' : '–' }}</td>
                        <td class="py-3 px-4 text-slate-600">
                            @if($license->isLifetime())
                                <span class="text-emerald-600 font-medium">Never</span>
                            @else
                                {{ $license->getValidUntilUtc() ? $license->getValidUntilUtc()->format('Y-m-d H:i:s') . ' UTC' : '–' }}
                            @endif
                        </td>
                        <td class="py-3 px-4 text-slate-600">{{ $license->allowed_domain ?? '–' }}</td>
                        <td class="py-3 px-4">
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
                            @elseif($license->statusLabel() === 'lifetime')
                                <span class="inline-flex items-center gap-1.5 text-emerald-600 font-medium">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Active (Lifetime)
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 text-emerald-600 font-medium">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Active
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 license-row-actions">
                            @if($license->statusLabel() !== 'revoked')
                                <form method="post" action="{{ route('admin.licenses.destroy', $license) }}" onsubmit="return confirm('Revoke this license?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 focus:ring-2 focus:ring-red-500 focus:ring-offset-1 transition-colors">Revoke</button>
                                </form>
                            @else
                                <span class="text-slate-400">–</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-12 px-4 text-center text-slate-500">
                            @if(request()->hasAny(['tool', 'status', 'search']))
                                No licenses match the filter. <a href="{{ route('admin.licenses.index') }}" class="text-sky-500 font-medium hover:underline">Clear filters</a> or <a href="{{ route('admin.licenses.create') }}" class="text-sky-500 font-medium hover:underline">add a license</a>.
                            @else
                                No licenses yet. <a href="{{ route('admin.licenses.create') }}" class="text-sky-500 font-medium hover:underline">Add one</a>.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($licenses->hasPages())
        <div class="px-4 py-3 border-t border-slate-200 bg-slate-50/50">
            {{ $licenses->links('pagination::tailwind') }}
        </div>
    @endif
</div>
@push('scripts')
<script>
(function () {
    // ── Row click to navigate ──────────────────────────────────────────────
    document.querySelectorAll('.license-row[data-href]').forEach(function (tr) {
        tr.addEventListener('click', function (e) {
            if (e.target.closest('.license-row-actions') || e.target.closest('.license-copy-btn')) return;
            var href = tr.getAttribute('data-href');
            if (href) window.location = href;
        });
    });

    // ── Copy token ────────────────────────────────────────────────────────
    document.querySelectorAll('.license-copy-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var text = btn.getAttribute('data-copy');
            if (!text) return;
            var msg = btn.parentElement.querySelector('.license-copy-msg');
            navigator.clipboard.writeText(text).then(function () {
                if (msg) { msg.style.opacity = '1'; setTimeout(function () { msg.style.opacity = '0'; }, 2000); }
            });
        });
    });

    // ── Bulk selection ────────────────────────────────────────────────────
    var selectAll   = document.getElementById('select-all-checkbox');
    var bulkBar     = document.getElementById('bulk-action-bar');
    var bulkLabel   = document.getElementById('bulk-count-label');
    var deselectBtn = document.getElementById('bulk-deselect-btn');

    function getChecked() {
        return Array.from(document.querySelectorAll('.license-checkbox:checked'));
    }

    function syncBulkBar() {
        var checked = getChecked();
        var count   = checked.length;
        if (count > 0) {
            bulkBar.classList.remove('hidden');
            bulkLabel.textContent = count + ' selected';
        } else {
            bulkBar.classList.add('hidden');
        }
        // Sync select-all indeterminate state
        var all = document.querySelectorAll('.license-checkbox');
        selectAll.indeterminate = count > 0 && count < all.length;
        selectAll.checked = count > 0 && count === all.length;
    }

    function buildHiddenIds(containerId, ids) {
        var container = document.getElementById(containerId);
        container.innerHTML = '';
        ids.forEach(function (id) {
            var input = document.createElement('input');
            input.type  = 'hidden';
            input.name  = 'ids[]';
            input.value = id;
            container.appendChild(input);
        });
    }

    document.querySelectorAll('.license-checkbox').forEach(function (cb) {
        cb.addEventListener('change', syncBulkBar);
    });

    selectAll.addEventListener('change', function () {
        document.querySelectorAll('.license-checkbox').forEach(function (cb) {
            cb.checked = selectAll.checked;
        });
        syncBulkBar();
    });

    deselectBtn.addEventListener('click', function () {
        document.querySelectorAll('.license-checkbox').forEach(function (cb) { cb.checked = false; });
        selectAll.checked = false;
        syncBulkBar();
    });

    // ── Bulk form submission ──────────────────────────────────────────────
    window.confirmBulkAction = function (form, action) {
        var ids = getChecked().map(function (cb) { return cb.value; });
        if (ids.length === 0) { alert('No licenses selected.'); return false; }

        var isDelete = action === 'permanently delete';
        var msg = isDelete
            ? 'Permanently DELETE ' + ids.length + ' license(s)? This cannot be undone and will also remove all installation history linked to them.'
            : 'Revoke ' + ids.length + ' license(s)?';

        if (!confirm(msg)) return false;

        var containerId = isDelete ? 'bulk-delete-ids' : 'bulk-revoke-ids';
        buildHiddenIds(containerId, ids);
        return true;
    };
})();
</script>
@endpush
@endsection
