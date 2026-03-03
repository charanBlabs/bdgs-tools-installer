@extends('layouts.admin')

@section('title', 'Developer manual – Tools')
@section('heading', 'Developer manual')

@section('content')
<p class="text-slate-600 text-sm mb-6">Reference for company developers. Click a tool to open its manual: install guide, features, versions, and feature notes for future builds.</p>

<div class="mb-6">
    <form method="get" action="{{ route('admin.tools.index') }}" class="flex flex-wrap items-center gap-3">
        <label for="product_type" class="text-sm font-medium text-slate-600">Product type</label>
        <select id="product_type" name="product_type" class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 bg-white" onchange="this.form.submit()">
            <option value="">All</option>
            <option value="service" {{ request('product_type') === 'service' ? 'selected' : '' }}>Service</option>
            <option value="quick_service" {{ request('product_type') === 'quick_service' ? 'selected' : '' }}>Quick Service</option>
            <option value="flagship_service" {{ request('product_type') === 'flagship_service' ? 'selected' : '' }}>Flagship Service</option>
            <option value="tool" {{ request('product_type') === 'tool' ? 'selected' : '' }}>Tool</option>
        </select>
        @if(request('product_type'))
            <a href="{{ route('admin.tools.index') }}" class="text-sm text-slate-500 hover:underline">Clear</a>
        @endif
    </form>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    @foreach($tools as $slug => $config)
        @php
            $doc = $docs[$slug] ?? null;
            $img = $doc?->image_url;
            $productType = $config['product_type'] ?? 'tool';
            $label = \App\Models\ToolDocumentation::PRODUCT_TYPE_LABELS[$productType] ?? $productType;
        @endphp
        <a href="{{ route('admin.tools.show', $slug) }}" class="group block bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-lg hover:border-sky-200 transition-all duration-200 animate-slide-up">
            <div class="aspect-[4/3] bg-gradient-to-br from-slate-100 to-slate-200 relative overflow-hidden">
                @if($img)
                    <img src="{{ $img }}" alt="{{ $config['name'] ?? $slug }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                @else
                    <div class="w-full h-full flex items-center justify-center text-slate-400">
                        <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                @endif
                <span class="absolute top-2 right-2 px-2 py-1 text-xs font-medium rounded-md
                    @if($productType === 'service') bg-violet-100 text-violet-800
                    @elseif($productType === 'quick_service') bg-emerald-100 text-emerald-800
                    @elseif($productType === 'flagship_service') bg-amber-100 text-amber-800
                    @else bg-slate-200 text-slate-700
                    @endif">
                    {{ $label }}
                </span>
            </div>
            <div class="p-4">
                <h3 class="font-semibold text-slate-900 group-hover:text-sky-600 transition-colors">{{ $config['name'] ?? $slug }}</h3>
                <p class="text-xs text-slate-500 mt-0.5">v{{ $config['version'] ?? '1.0' }} · {{ $config['type'] ?? 'service' }}</p>
            </div>
        </a>
    @endforeach
</div>

@if(empty($tools))
    <div class="bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-500">
        No tools match the selected product type. <a href="{{ route('admin.tools.index') }}" class="text-sky-500 hover:underline">Show all</a>.
    </div>
@endif
@endsection
