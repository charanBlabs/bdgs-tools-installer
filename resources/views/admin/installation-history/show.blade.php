@extends('layouts.admin')

@section('title', 'Installation details')
@section('heading', 'Installation details')

@section('top_actions')
    <a href="{{ route('admin.installation-history.index') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">← Back to history</a>
@endsection

@section('content')
<div class="space-y-6 max-w-3xl">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden animate-slide-up">
        <div class="px-5 py-4 border-b border-slate-200">
            <h2 class="text-base font-semibold text-slate-900">Installation record</h2>
        </div>
        <div class="p-5">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="font-medium text-slate-500 uppercase tracking-wider text-xs">Date</dt>
                    <dd class="mt-1 text-slate-800">{{ $history->created_at->format('M j, Y g:i A') }} UTC</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500 uppercase tracking-wider text-xs">Tool</dt>
                    <dd class="mt-1 text-slate-800">{{ $history->tool_name }} ({{ $history->tool_slug }})</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500 uppercase tracking-wider text-xs">License</dt>
                    <dd class="mt-1 text-slate-800">
                        @if($history->license)
                            <a href="{{ route('admin.licenses.show', $history->license) }}" class="text-sky-600 hover:underline font-mono text-xs">{{ Str::limit($history->license->token, 36) }}</a>
                        @else
                            – (direct tool or no license)
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500 uppercase tracking-wider text-xs">Install domain</dt>
                    <dd class="mt-1 text-slate-800">{{ $history->install_domain ?? '–' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500 uppercase tracking-wider text-xs">BD base URL</dt>
                    <dd class="mt-1 text-slate-800 break-all">{{ $history->bd_base_url ?? '–' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500 uppercase tracking-wider text-xs">Source</dt>
                    <dd class="mt-1 text-slate-800">{{ $history->source }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500 uppercase tracking-wider text-xs">Status</dt>
                    <dd class="mt-1">
                        @if($history->success)
                            <span class="inline-flex items-center gap-1.5 text-emerald-600 font-medium">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Success
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-amber-600 font-medium">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span> Failed (partial or error)
                            </span>
                        @endif
                    </dd>
                </div>
                @if($history->order_id)
                <div>
                    <dt class="font-medium text-slate-500 uppercase tracking-wider text-xs">Order ID</dt>
                    <dd class="mt-1 text-slate-800">{{ $history->order_id }}</dd>
                </div>
                @endif
                @if($history->customer_id)
                <div>
                    <dt class="font-medium text-slate-500 uppercase tracking-wider text-xs">Customer ID</dt>
                    <dd class="mt-1 text-slate-800">{{ $history->customer_id }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    @if(!empty($history->details))
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden animate-slide-up">
        <div class="px-5 py-4 border-b border-slate-200">
            <h2 class="text-base font-semibold text-slate-900">Install details (widgets)</h2>
        </div>
        <div class="p-5">
            @if(isset($history->details['widgets']) && is_array($history->details['widgets']))
                <ul class="space-y-2">
                    @foreach($history->details['widgets'] as $w)
                        <li class="flex items-center gap-2 text-sm">
                            @if($w['ok'] ?? false)
                                <span class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </span>
                                <span class="text-slate-700">{{ $w['widget'] ?? $w['widget_name'] ?? 'Widget' }}{{ isset($w['widget_id']) && $w['widget_id'] ? ' (ID ' . $w['widget_id'] . ')' : '' }}</span>
                            @else
                                <span class="w-5 h-5 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </span>
                                <span class="text-slate-700">{{ $w['widget'] ?? $w['widget_name'] ?? 'Widget' }}: {{ $w['message'] ?? 'Failed' }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
                @if(isset($history->details['widget_count']))
                    <p class="text-xs text-slate-500 mt-3">{{ $history->details['widget_count'] }} widget(s) in this install.</p>
                @endif
            @else
                <pre class="text-xs bg-slate-50 p-4 rounded-lg overflow-x-auto">{{ json_encode($history->details, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection
