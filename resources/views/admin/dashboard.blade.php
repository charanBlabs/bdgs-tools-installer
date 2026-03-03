@extends('layouts.admin')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden transition-shadow hover:shadow-md animate-slide-up" style="animation-delay: 0.05s;">
        <div class="p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">Total Licenses</p>
            <p class="text-3xl font-bold text-slate-900">{{ $totalLicenses }}</p>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden transition-shadow hover:shadow-md animate-slide-up" style="animation-delay: 0.1s;">
        <div class="p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">Active Licenses</p>
            <p class="text-3xl font-bold text-emerald-600">{{ $activeLicenses }}</p>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden transition-shadow hover:shadow-md animate-slide-up" style="animation-delay: 0.15s;">
        <div class="p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">Tools</p>
            <p class="text-3xl font-bold text-slate-900">{{ count($tools) }}</p>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm mb-6 animate-slide-up" style="animation-delay: 0.2s;">
    <div class="px-5 py-4 border-b border-slate-200">
        <h2 class="text-base font-semibold text-slate-900">Quick actions</h2>
    </div>
    <div class="p-5">
        <p class="text-slate-600 text-sm mb-4">Manage licenses and install tools to BD sites (internal use). BDGS order flow and API settings will be available here later.</p>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.licenses.create') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-sky-500 rounded-lg hover:bg-sky-600 focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transition-all shadow-sm hover:shadow">Add license</a>
            <a href="{{ route('admin.licenses.index') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transition-colors">View all licenses</a>
            <a href="{{ route('admin.tools.index') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transition-colors">Developer manual</a>
            <a href="{{ route('admin.install.form') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transition-colors">Installer</a>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm mb-6 animate-slide-up" style="animation-delay: 0.22s;">
    <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-base font-semibold text-slate-900">Tools &amp; versions</h2>
        <form method="get" action="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
            <label for="dashboard_product_type" class="text-xs font-medium text-slate-500">Product type</label>
            <select id="dashboard_product_type" name="product_type" class="px-2 py-1.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 bg-white" onchange="this.form.submit()">
                <option value="">All</option>
                <option value="service" {{ request('product_type') === 'service' ? 'selected' : '' }}>service</option>
                <option value="quick_service" {{ request('product_type') === 'quick_service' ? 'selected' : '' }}>quick_service</option>
                <option value="flagship_service" {{ request('product_type') === 'flagship_service' ? 'selected' : '' }}>flagship_service</option>
                <option value="tool" {{ request('product_type') === 'tool' ? 'selected' : '' }}>tool</option>
            </select>
            @if(request('product_type'))
                <a href="{{ route('admin.dashboard') }}" class="text-xs text-slate-500 hover:underline">Clear</a>
            @endif
        </form>
    </div>
    <div class="p-5 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200">
                    <th class="text-left py-2 px-3 font-semibold text-slate-600">Tool</th>
                    <th class="text-left py-2 px-3 font-semibold text-slate-600">Version</th>
                    <th class="text-left py-2 px-3 font-semibold text-slate-600">Type</th>
                    <th class="text-left py-2 px-3 font-semibold text-slate-600">Product type</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tools as $slug => $config)
                    <tr class="border-b border-slate-100">
                        <td class="py-2 px-3 font-medium text-slate-800">{{ $config['name'] ?? $slug }}</td>
                        <td class="py-2 px-3 text-slate-600">{{ $config['version'] ?? '1.0' }}</td>
                        <td class="py-2 px-3 text-slate-600">{{ $config['type'] ?? 'service' }}</td>
                        <td class="py-2 px-3 text-slate-600">{{ $config['product_type'] ?? 'tool' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="text-xs text-slate-500 mt-3">Update <code class="bg-slate-100 px-1 rounded">config/tools.php</code> to set version (e.g. 1.1, 2) and product_type when pushing updates.</p>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm mb-6 animate-slide-up" style="animation-delay: 0.25s;">
    <div class="px-5 py-4 border-b border-slate-200">
        <h2 class="text-base font-semibold text-slate-900">BDGS Orders</h2>
    </div>
    <div class="p-5">
        <p class="text-slate-600 text-sm m-0">Orders from the BDGS website will appear here. Install can be triggered manually or via API from BDGS after purchase. <strong>Coming soon.</strong></p>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden animate-slide-up" style="animation-delay: 0.3s;">
    <div class="px-5 py-4 border-b border-slate-200">
        <h2 class="text-base font-semibold text-slate-900">API endpoints (for BDGS / developers)</h2>
    </div>
    <div class="p-5">
        <p class="text-slate-600 text-sm mb-4">Use these endpoints to verify BD tokens and run installs from the BDGS site or your own scripts. All accept JSON; responses are JSON.</p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200">
                        <th class="text-left py-3 px-4 font-semibold text-slate-600 uppercase tracking-wider">Method</th>
                        <th class="text-left py-3 px-4 font-semibold text-slate-600 uppercase tracking-wider">Endpoint</th>
                        <th class="text-left py-3 px-4 font-semibold text-slate-600 uppercase tracking-wider">Purpose</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4"><code class="text-xs bg-slate-100 px-2 py-1 rounded text-slate-700">GET</code></td>
                        <td class="py-3 px-4"><code class="text-xs bg-slate-100 px-2 py-1 rounded text-slate-700">/api/tools</code></td>
                        <td class="py-3 px-4 text-slate-600">List available tools (slug, name, type)</td>
                    </tr>
                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4"><code class="text-xs bg-slate-100 px-2 py-1 rounded text-slate-700">POST</code></td>
                        <td class="py-3 px-4"><code class="text-xs bg-slate-100 px-2 py-1 rounded text-slate-700">/api/verify</code></td>
                        <td class="py-3 px-4 text-slate-600">Verify BD token. Body: <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">bd_base_url</code>, <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">bd_api_key</code></td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4"><code class="text-xs bg-slate-100 px-2 py-1 rounded text-slate-700">POST</code></td>
                        <td class="py-3 px-4"><code class="text-xs bg-slate-100 px-2 py-1 rounded text-slate-700">/api/install</code></td>
                        <td class="py-3 px-4 text-slate-600">Install tool. Body: <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">license_token</code>, <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">bd_base_url</code>, <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">bd_api_key</code>, <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">tool_slug</code>, optional <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">install_domain</code></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
