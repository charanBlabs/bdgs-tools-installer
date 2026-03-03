@extends('layouts.admin')

@section('title', ($config['name'] ?? $toolSlug) . ' – Developer manual')
@section('heading', $config['name'] ?? $toolSlug)

@section('top_actions')
    @if(($config['widgets'] ?? null) && count($config['widgets']) > 0 && $doc->installation_type !== 'manual_only')
        <a href="{{ route('admin.install.form', ['tool' => $toolSlug]) }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-sky-500 rounded-lg hover:bg-sky-600 focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
            Run installer
        </a>
    @endif
    <a href="{{ route('admin.tools.edit', $toolSlug) }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Edit manual</a>
    <a href="{{ route('admin.tools.index') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">← All tools</a>
@endsection

@section('content')
@php
    $productType = $config['product_type'] ?? 'tool';
    $productLabel = \App\Models\ToolDocumentation::PRODUCT_TYPE_LABELS[$productType] ?? $productType;
    $screenshots = $doc->screenshots ?? [];
@endphp

{{-- Hero: large image + product type + version --}}
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-6 animate-slide-up">
    <div class="aspect-[21/9] max-h-[320px] bg-gradient-to-br from-slate-100 to-slate-200 relative">
        @if($doc->image_url)
            <img src="{{ $doc->image_url }}" alt="{{ $config['name'] ?? $toolSlug }}" class="w-full h-full object-cover">
        @else
            <div class="w-full h-full flex items-center justify-center text-slate-400">
                <svg class="w-24 h-24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
        @endif
        <div class="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-black/60 to-transparent flex flex-wrap items-end justify-between gap-2">
            <div>
                <span class="inline-block px-2.5 py-1 text-xs font-medium rounded-md
                    @if($productType === 'service') bg-violet-500/90 text-white
                    @elseif($productType === 'quick_service') bg-emerald-500/90 text-white
                    @elseif($productType === 'flagship_service') bg-amber-500/90 text-white
                    @else bg-slate-600/90 text-white
                    @endif">
                    {{ $productLabel }}
                </span>
                <span class="ml-2 text-white/90 text-sm">Version {{ $config['version'] ?? '1.0' }}</span>
            </div>
        </div>
    </div>
</div>

{{-- Screenshots carousel --}}
@if(count($screenshots) > 0)
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-6 animate-slide-up">
    <div class="px-5 py-4 border-b border-slate-200">
        <h2 class="text-base font-semibold text-slate-900">Screenshots</h2>
    </div>
    <div class="p-4">
        <div class="screenshots-carousel flex gap-4 overflow-x-auto pb-2 snap-x snap-mandatory" style="scroll-snap-type: x mandatory;">
            @foreach($screenshots as $url)
                <div class="flex-shrink-0 w-[280px] snap-center rounded-lg overflow-hidden border border-slate-200">
                    <img src="{{ $url }}" alt="Screenshot" class="w-full h-auto object-cover">
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        {{-- Developer stats --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden animate-slide-up">
            <div class="px-5 py-4 border-b border-slate-200">
                <h2 class="text-base font-semibold text-slate-900">Details for developers</h2>
            </div>
            <div class="p-5">
                <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                    <div>
                        <dt class="text-slate-500 uppercase tracking-wider text-xs">Widgets</dt>
                        <dd class="font-semibold text-slate-900 mt-0.5">{{ $widgetCount }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 uppercase tracking-wider text-xs">Type</dt>
                        <dd class="font-medium text-slate-700 mt-0.5">{{ $config['type'] ?? 'service' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 uppercase tracking-wider text-xs">Install type</dt>
                        <dd class="mt-0.5">
                            @if($doc->installation_type === 'quick_install')
                                <span class="inline-flex items-center gap-1 text-emerald-600 font-medium">100% Quick installer</span>
                            @elseif($doc->installation_type === 'installer_plus_manual')
                                <span class="inline-flex items-center gap-1 text-amber-600 font-medium">Installer + manual</span>
                            @else
                                <span class="inline-flex items-center gap-1 text-slate-600 font-medium">Manual only</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 uppercase tracking-wider text-xs">Files used</dt>
                        <dd class="font-medium text-slate-700 mt-0.5">{{ count($filesUsed) }}</dd>
                    </div>
                </dl>
                @if(!empty($filesUsed))
                    <p class="text-xs text-slate-500 mt-3">Files: <code class="bg-slate-100 px-1 rounded">{{ implode(', ', $filesUsed) }}</code></p>
                @endif
            </div>
        </div>

        {{-- Installation guide --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden animate-slide-up">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-900">Installation guide</h2>
                @if($doc->installation_type === 'quick_install' && $widgetCount > 0)
                    <span class="text-xs font-medium text-emerald-600 bg-emerald-50 px-2 py-1 rounded">Direct steps · no manual</span>
                @endif
            </div>
            <div class="p-5 prose prose-slate prose-sm max-w-none">
                @if($doc->installation_steps)
                    <div class="whitespace-pre-wrap text-slate-700">{{ $doc->installation_steps }}</div>
                @else
                    <p class="text-slate-500 italic">No steps added yet. <a href="{{ route('admin.tools.edit', $toolSlug) }}">Edit manual</a> to add step-by-step instructions.</p>
                    @if($widgetCount > 0 && $doc->installation_type !== 'manual_only')
                        <p class="text-slate-600 mt-2">Use <strong>Run installer</strong> above to install widgets to a BD site. Ensure BD API token is verified and (for service tools) a valid license is used.</p>
                    @endif
                @endif
            </div>
            @if($doc->installation_type === 'installer_plus_manual' && $doc->manual_steps)
                <div class="px-5 pb-5 pt-0">
                    <h3 class="text-sm font-semibold text-slate-700 mb-2">Manual steps (after install)</h3>
                    <div class="whitespace-pre-wrap text-slate-600 text-sm bg-amber-50 border border-amber-100 rounded-lg p-4">{{ $doc->manual_steps }}</div>
                </div>
            @endif
        </div>

        {{-- Features --}}
        @if(!empty($doc->features))
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden animate-slide-up">
            <div class="px-5 py-4 border-b border-slate-200">
                <h2 class="text-base font-semibold text-slate-900">Features</h2>
            </div>
            <ul class="p-5 list-disc list-inside space-y-1 text-sm text-slate-700">
                @foreach($doc->features as $f)
                    <li>{{ $f }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- UI description --}}
        @if($doc->ui_description)
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden animate-slide-up">
            <div class="px-5 py-4 border-b border-slate-200">
                <h2 class="text-base font-semibold text-slate-900">UI overview</h2>
            </div>
            <div class="p-5 text-sm text-slate-700 whitespace-pre-wrap">{{ $doc->ui_description }}</div>
        </div>
        @endif

        {{-- Feature notes (future) --}}
        @if($doc->feature_notes)
        <div class="bg-white rounded-xl border border-sky-50 border-slate-200 shadow-sm overflow-hidden animate-slide-up">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center gap-2">
                <h2 class="text-base font-semibold text-slate-900">Feature notes (for future versions)</h2>
                <span class="text-xs text-slate-500">Use these to build new features</span>
            </div>
            <div class="p-5 text-sm text-slate-700 whitespace-pre-wrap bg-sky-50/50">{{ $doc->feature_notes }}</div>
        </div>
        @endif

        {{-- Version notes --}}
        @if($doc->version_notes)
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden animate-slide-up">
            <div class="px-5 py-4 border-b border-slate-200">
                <h2 class="text-base font-semibold text-slate-900">Version notes</h2>
            </div>
            <div class="p-5 text-sm text-slate-700 whitespace-pre-wrap">{{ $doc->version_notes }}</div>
        </div>
        @endif
    </div>

    <div class="space-y-6">
        {{-- Widget list --}}
        @if($widgetCount > 0)
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden animate-slide-up">
            <div class="px-5 py-4 border-b border-slate-200">
                <h2 class="text-base font-semibold text-slate-900">Widgets ({{ $widgetCount }})</h2>
            </div>
            <ul class="p-5 space-y-2 text-sm">
                @foreach($config['widgets'] ?? [] as $w)
                    <li class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-sky-400"></span>
                        <span class="font-medium text-slate-800">{{ $w['widget_name'] ?? 'Widget' }}</span>
                        <span class="text-slate-500">({{ $w['widget_viewport'] ?? '–' }})</span>
                    </li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Quick actions --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden animate-slide-up">
            <div class="px-5 py-4 border-b border-slate-200">
                <h2 class="text-base font-semibold text-slate-900">Actions</h2>
            </div>
            <div class="p-5 space-y-2">
                @if($widgetCount > 0 && $doc->installation_type !== 'manual_only')
                    <a href="{{ route('admin.install.form', ['tool' => $toolSlug]) }}" class="block w-full text-center px-4 py-2.5 text-sm font-medium text-white bg-sky-500 rounded-lg hover:bg-sky-600">Run installer</a>
                @endif
                <a href="{{ route('admin.tools.edit', $toolSlug) }}" class="block w-full text-center px-4 py-2.5 text-sm font-medium text-slate-700 bg-slate-100 rounded-lg hover:bg-slate-200">Edit manual</a>
                <a href="{{ route('admin.tools.index') }}" class="block w-full text-center px-4 py-2.5 text-sm font-medium text-slate-600 hover:underline">All tools</a>
            </div>
        </div>
    </div>
</div>
@endsection
