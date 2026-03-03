@extends('layouts.admin')

@section('title', 'Installation history')
@section('heading', 'Installation history')

@section('content')
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden animate-slide-up mb-4">
    <div class="px-5 py-4 border-b border-slate-200">
        <form method="get" action="{{ route('admin.installation-history.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="filter_license" class="block text-xs font-medium text-slate-500 mb-1">License</label>
                <select id="filter_license" name="license_id" class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 bg-white min-w-[180px]">
                    <option value="">All licenses</option>
                    @foreach($licenses as $lic)
                        <option value="{{ $lic->id }}" {{ request('license_id') == $lic->id ? 'selected' : '' }}>
                            {{ Str::limit($lic->token, 24) }} ({{ $lic->tool_slug }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter_tool" class="block text-xs font-medium text-slate-500 mb-1">Tool</label>
                <select id="filter_tool" name="tool_slug" class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 bg-white min-w-[140px]">
                    <option value="">All tools</option>
                    @foreach($tools as $slug => $config)
                        <option value="{{ $slug }}" {{ request('tool_slug') === $slug ? 'selected' : '' }}>{{ $config['name'] ?? $slug }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter_source" class="block text-xs font-medium text-slate-500 mb-1">Source</label>
                <select id="filter_source" name="source" class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 bg-white min-w-[100px]">
                    <option value="">All</option>
                    <option value="web" {{ request('source') === 'web' ? 'selected' : '' }}>Web</option>
                    <option value="api" {{ request('source') === 'api' ? 'selected' : '' }}>API</option>
                </select>
            </div>
            <div>
                <label for="filter_success" class="block text-xs font-medium text-slate-500 mb-1">Status</label>
                <select id="filter_success" name="success" class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 bg-white min-w-[100px]">
                    <option value="">All</option>
                    <option value="1" {{ request('success') === '1' ? 'selected' : '' }}>Success</option>
                    <option value="0" {{ request('success') === '0' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>
            <div>
                <label for="filter_from" class="block text-xs font-medium text-slate-500 mb-1">From date</label>
                <input type="date" id="filter_from" name="from_date" value="{{ request('from_date') }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
            </div>
            <div>
                <label for="filter_to" class="block text-xs font-medium text-slate-500 mb-1">To date</label>
                <input type="date" id="filter_to" name="to_date" value="{{ request('to_date') }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-sky-500 rounded-lg hover:bg-sky-600 focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">Filter</button>
                <a href="{{ route('admin.installation-history.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Clear</a>
            </div>
        </form>
    </div>
</div>

@if(request()->hasAny(['license_id', 'tool_slug', 'source', 'success', 'from_date', 'to_date']))
    <p class="text-sm text-slate-600 mb-2">
        @if($histories->total() > 0)
            {{ $histories->total() }} {{ Str::plural('record', $histories->total()) }} match the filter.
        @else
            No records match the current filter.
        @endif
    </p>
@endif

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden animate-slide-up">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50/80">
                    <th class="text-left py-3.5 px-4 font-semibold text-slate-600 uppercase tracking-wider">Date</th>
                    <th class="text-left py-3.5 px-4 font-semibold text-slate-600 uppercase tracking-wider">License</th>
                    <th class="text-left py-3.5 px-4 font-semibold text-slate-600 uppercase tracking-wider">Tool</th>
                    <th class="text-left py-3.5 px-4 font-semibold text-slate-600 uppercase tracking-wider">Domain</th>
                    <th class="text-left py-3.5 px-4 font-semibold text-slate-600 uppercase tracking-wider">Source</th>
                    <th class="text-left py-3.5 px-4 font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                    <th class="text-left py-3.5 px-4 font-semibold text-slate-600 uppercase tracking-wider">Details</th>
                </tr>
            </thead>
            <tbody>
                @forelse($histories as $h)
                    <tr class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors">
                        <td class="py-3 px-4 text-slate-600">{{ $h->created_at->format('Y-m-d H:i') }} UTC</td>
                        <td class="py-3 px-4">
                            @if($h->license)
                                <a href="{{ route('admin.licenses.show', $h->license) }}" class="text-sky-600 hover:underline font-mono text-xs">{{ Str::limit($h->license->token, 20) }}</a>
                            @else
                                <span class="text-slate-400">–</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-slate-700">{{ $h->tool_name }}</td>
                        <td class="py-3 px-4 text-slate-600">{{ $h->install_domain ?? '–' }}</td>
                        <td class="py-3 px-4 text-slate-600">{{ $h->source }}</td>
                        <td class="py-3 px-4">
                            @if($h->success)
                                <span class="inline-flex items-center gap-1.5 text-emerald-600 font-medium">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Success
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 text-amber-600 font-medium">
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span> Failed
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <a href="{{ route('admin.installation-history.show', $h) }}" class="text-sky-600 hover:underline">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-12 px-4 text-center text-slate-500">
                            @if(request()->hasAny(['license_id', 'tool_slug', 'source', 'success', 'from_date', 'to_date']))
                                No installation records match the filter. <a href="{{ route('admin.installation-history.index') }}" class="text-sky-500 font-medium hover:underline">Clear filters</a>.
                            @else
                                No installation history yet. Install a tool from the <a href="{{ route('admin.install.form') }}" class="text-sky-500 font-medium hover:underline">Installer</a> to see records here.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($histories->hasPages())
        <div class="px-4 py-3 border-t border-slate-200 bg-slate-50/50">
            {{ $histories->links('pagination::tailwind') }}
        </div>
    @endif
</div>
@endsection
