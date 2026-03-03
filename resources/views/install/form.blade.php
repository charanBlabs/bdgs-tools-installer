<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tool Installer – Install to your BD site</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['DM Sans', 'system-ui', 'sans-serif'] },
                    animation: {
                        'fade-in': 'fadeIn 0.4s ease-out forwards',
                        'slide-up': 'slideUp 0.45s ease-out forwards',
                        'slide-down': 'slideDown 0.35s ease-out forwards',
                        'scale-in': 'scaleIn 0.3s ease-out forwards',
                    },
                    keyframes: {
                        fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                        slideUp: { '0%': { opacity: '0', transform: 'translateY(20px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                        slideDown: { '0%': { opacity: '0', transform: 'translateY(-10px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                        scaleIn: { '0%': { opacity: '0', transform: 'scale(0.96)' }, '100%': { opacity: '1', transform: 'scale(1)' } },
                    },
                },
            },
        };
    </script>
    <style>
        .step-badge { opacity: 0; animation: slideUp 0.4s ease-out forwards; }
        .step-badge:nth-child(1) { animation-delay: 0.1s; }
        .step-badge:nth-child(2) { animation-delay: 0.2s; }
        .install-result-item { opacity: 0; animation: slideUp 0.35s ease-out forwards; }
        .install-result-item.delay-0 { animation-delay: 0.1s; }
        .install-result-item.delay-1 { animation-delay: 0.16s; }
        .install-result-item.delay-2 { animation-delay: 0.22s; }
        .install-result-item.delay-3 { animation-delay: 0.28s; }
        .install-result-item.delay-4 { animation-delay: 0.34s; }
        .install-result-item.delay-5 { animation-delay: 0.4s; }
        .install-result-item.delay-6 { animation-delay: 0.46s; }
        .install-result-item.delay-7 { animation-delay: 0.52s; }
        .install-result-item.delay-8 { animation-delay: 0.58s; }
        .install-result-item.delay-9 { animation-delay: 0.64s; }
        .install-result-item.delay-10 { animation-delay: 0.7s; }
        #install-loader { display: none; position: fixed; top: 0; left: 0; right: 0; z-index: 9999; padding: 12px 16px; background: #0f172a; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2); }
        #install-loader.show { display: block; }
        #install-loader .progress-bar { width: 100%; max-width: 400px; height: 8px; background: rgba(255,255,255,0.2); border-radius: 9999px; overflow: hidden; }
        #install-loader .progress-fill { height: 100%; background: linear-gradient(90deg, #0ea5e9, #38bdf8); border-radius: 9999px; transition: width 0.35s ease; width: 0%; }
    </style>
</head>
<body class="font-sans antialiased text-slate-700 bg-gradient-to-br from-slate-50 via-slate-100 to-sky-50 min-h-screen">
    <div id="install-loader" aria-hidden="true">
        <div class="flex items-center gap-4">
            <p id="install-loader-text" class="text-white font-medium text-sm whitespace-nowrap">Installing...</p>
            <div class="progress-bar flex-1 min-w-0"><div id="install-progress-fill" class="progress-fill"></div></div>
            <p id="install-loader-pct" class="text-white/90 text-sm font-medium tabular-nums">0%</p>
        </div>
    </div>
    <div class="min-h-screen flex flex-col">
        {{-- Header --}}
        <header class="border-b border-slate-200/80 bg-white/80 backdrop-blur-sm sticky top-0 z-10 animate-slide-down">
            <div class="max-w-2xl mx-auto px-4 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-sky-500 flex items-center justify-center text-white shadow-lg shadow-sky-500/25">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-slate-900">Tool Installer</h1>
                        <p class="text-xs text-slate-500">Install tools to your Brilliant Directories site</p>
                    </div>
                </div>
                <a href="{{ route('admin.dashboard') }}" class="text-sm font-medium text-slate-600 hover:text-sky-600 transition-colors">Dashboard</a>
            </div>
        </header>

        <main class="flex-1 max-w-2xl w-full mx-auto px-4 py-8 md:py-12">
            {{-- Alerts --}}
            @if(session('success'))
                <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-start gap-3 animate-slide-up" role="alert">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <p class="text-emerald-800 text-sm font-medium pt-1">{{ session('success') }}</p>
                </div>
            @endif
            @if(session('error'))
                <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 flex items-start gap-3 animate-slide-up" role="alert">
                    <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="text-red-800 text-sm font-medium pt-1">{{ session('error') }}</p>
                </div>
            @endif
            @if(session('warning'))
                <div class="mb-6 p-4 rounded-xl bg-amber-50 border border-amber-200 flex items-start gap-3 animate-slide-up" role="alert">
                    <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <p class="text-amber-800 text-sm font-medium pt-1">{{ session('warning') }}</p>
                </div>
            @endif

            @if(!empty($installConfirmNeeded) && !empty($existingWidgets))
                <div class="mb-6 p-5 rounded-2xl bg-amber-50 border border-amber-200 animate-slide-up">
                    <p class="font-semibold text-amber-900 mb-2">Widgets already exist on this site</p>
                    <p class="text-sm text-amber-800 mb-3">The following widgets are already installed. Choose <strong>Update</strong> to overwrite them with the current tool version, or <strong>Cancel</strong> to do nothing.</p>
                    <ul class="list-disc list-inside text-sm text-amber-800 mb-4">
                        @foreach($existingWidgets as $w)
                            <li>{{ $w['widget_name'] ?? 'Widget' }}{{ isset($w['widget_id']) ? ' (ID ' . $w['widget_id'] . ')' : '' }}</li>
                        @endforeach
                    </ul>
                    <form method="post" action="{{ route('admin.install.run') }}" class="inline mr-2">
                        @csrf
                        <input type="hidden" name="bd_base_url" value="{{ $bdBaseUrl }}">
                        <input type="hidden" name="bd_api_key" value="{{ $bdApiKey ?? '' }}">
                        <input type="hidden" name="tool_slug" value="{{ $toolSlug ?? '' }}">
                        <input type="hidden" name="license_token" value="{{ $licenseToken ?? '' }}">
                        <input type="hidden" name="install_domain" value="{{ old('install_domain', '') }}">
                        <input type="hidden" name="install_confirm" value="update">
                        @if(old('plain_install'))
                        <input type="hidden" name="plain_install" value="1">
                        @endif
                        @if(old('enforce_license'))
                        <input type="hidden" name="enforce_license" value="1">
                        @endif
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-amber-600 rounded-xl hover:bg-amber-700 focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">Update existing</button>
                    </form>
                    <a href="{{ route('admin.install.form') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancel</a>
                </div>
            @endif

            @if(session('install_success') && session('install_results'))
                <div class="mb-8 p-5 rounded-2xl bg-emerald-50 border border-emerald-200 animate-scale-in">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 rounded-full bg-emerald-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-emerald-900">Install completed</h2>
                            <p class="text-sm text-emerald-700">Widgets were installed or updated on your site.</p>
                        </div>
                    </div>
                    <ul class="space-y-2">
                        @foreach(session('install_results') as $i => $r)
                            <li class="install-result-item delay-{{ min($i, 10) }} flex items-center gap-2 text-sm text-emerald-800">
                                @if($r['ok'])
                                    <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>{{ $r['widget'] }}{{ isset($r['widget_id']) && $r['widget_id'] ? ' (ID ' . $r['widget_id'] . ')' : '' }}</span>
                                @else
                                    <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>{{ $r['widget'] }}: {{ $r['message'] ?? 'Failed' }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @elseif(session('install_results'))
                <div class="mb-6 p-4 rounded-xl bg-slate-50 border border-slate-200 animate-slide-up">
                    <p class="text-sm font-medium text-slate-700 mb-2">Install results</p>
                    <ul class="space-y-1 text-sm text-slate-600">
                        @foreach(session('install_results') as $r)
                            <li>{{ $r['widget'] }}: {{ $r['ok'] ? 'OK' : ($r['message'] ?? 'Failed') }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Step 1: Connect BD --}}
            <section class="mb-8 animate-slide-up" style="animation-delay: 0.05s;">
                <div class="flex items-center gap-3 mb-4">
                    <span class="step-badge flex items-center justify-center w-8 h-8 rounded-full bg-sky-500 text-white text-sm font-bold shadow-lg shadow-sky-500/30">1</span>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Connect your BD site</h2>
                        <p class="text-sm text-slate-500">Verify your Brilliant Directories API token</p>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm shadow-slate-200/50 overflow-hidden transition-shadow hover:shadow-md">
                    <form method="post" action="{{ route('admin.install.verify') }}" class="p-5 md:p-6 space-y-4">
                        @csrf
                        <div>
                            <label for="bd_base_url" class="block text-sm font-medium text-slate-700 mb-1.5">BD Base URL</label>
                            <input type="url" id="bd_base_url" name="bd_base_url" value="{{ $bdBaseUrl }}" placeholder="https://yoursite.directoryup.com" required autocomplete="url" class="w-full px-4 py-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-all placeholder-slate-400">
                        </div>
                        <div>
                            <label for="bd_api_key" class="block text-sm font-medium text-slate-700 mb-1.5">BD API Key</label>
                            <input type="password" id="bd_api_key" name="bd_api_key" value="{{ $bdApiKey ?? '' }}" placeholder="Your BD API key" required autocomplete="off" class="w-full px-4 py-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-all placeholder-slate-400">
                        </div>
                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-3 text-sm font-medium text-white bg-sky-500 rounded-xl hover:bg-sky-600 focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transition-all shadow-sm hover:shadow hover:shadow-sky-500/25">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            Verify token
                        </button>
                    </form>
                </div>
            </section>

            @if(!empty($supportsServerFetch))
            {{-- Setup tool: upload server files + optional custom URL (for server_fetch tools) --}}
            <section class="mb-8 animate-slide-up" style="animation-delay: 0.1s;">
                <div class="flex items-center gap-3 mb-4">
                    <span class="step-badge flex items-center justify-center w-8 h-8 rounded-full bg-violet-500 text-white text-sm font-bold shadow-lg shadow-violet-500/30">1.5</span>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Setup tool (server files)</h2>
                        <p class="text-sm text-slate-500">Upload HTML/JS to be served from your server; widget will fetch them at runtime. Optional: set a custom base URL (e.g. CDN).</p>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm shadow-slate-200/50 overflow-hidden transition-shadow hover:shadow-md">
                    <form method="post" action="{{ route('admin.install.setup') }}" enctype="multipart/form-data" class="p-5 md:p-6 space-y-4">
                        @csrf
                        <input type="hidden" name="tool_slug" value="{{ $toolSlug }}">
                        <div>
                            <label for="server_files" class="block text-sm font-medium text-slate-700 mb-1.5">Server files (HTML, JS, etc.)</label>
                            <input type="file" id="server_files" name="server_files[]" multiple accept=".html,.htm,.js,.css" class="w-full px-4 py-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-sky-500 focus:border-sky-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100">
                            <p class="text-xs text-slate-500 mt-1">These will be stored on this server and fetched by the widget using your license token.</p>
                        </div>
                        <div>
                            <label for="custom_base_url" class="block text-sm font-medium text-slate-700 mb-1.5">Custom base URL <span class="text-slate-400 font-normal">(optional)</span></label>
                            <input type="url" id="custom_base_url" name="custom_base_url" value="{{ old('custom_base_url') }}" placeholder="https://cdn.example.com/tool-assets/{{ $toolSlug }}" class="w-full px-4 py-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-all placeholder-slate-400">
                        </div>
                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-3 text-sm font-medium text-white bg-violet-500 rounded-xl hover:bg-violet-600 focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 transition-all shadow-sm">
                            Save setup
                        </button>
                    </form>
                </div>
            </section>
            @endif

            {{-- Step 2: Choose tool & install --}}
            <section class="animate-slide-up" style="animation-delay: 0.15s;">
                <div class="flex items-center gap-3 mb-4">
                    <span class="step-badge flex items-center justify-center w-8 h-8 rounded-full bg-slate-700 text-white text-sm font-bold shadow-lg shadow-slate-700/30">2</span>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Choose tool &amp; install</h2>
                        <p class="text-sm text-slate-500">Select a tool and run the installer</p>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm shadow-slate-200/50 overflow-hidden transition-shadow hover:shadow-md">
                    <form id="install-run-form" method="post" action="{{ route('admin.install.run') }}" class="p-5 md:p-6 space-y-4">
                        @csrf
                        <input type="hidden" name="bd_base_url" value="{{ $bdBaseUrl }}">
                        <input type="hidden" name="bd_api_key" value="{{ $bdApiKey ?? '' }}">
                        <div>
                            <label for="tool_slug" class="block text-sm font-medium text-slate-700 mb-1.5">Tool</label>
                            <select id="tool_slug" name="tool_slug" required class="w-full px-4 py-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-all bg-white">
                                @foreach($tools as $slug => $config)
                                    <option value="{{ $slug }}" {{ ($toolSlug ?? '') === $slug ? 'selected' : '' }}>{{ $config['name'] ?? $slug }} ({{ $config['type'] ?? 'service' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="license_group">
                            <label for="license_token" class="block text-sm font-medium text-slate-700 mb-1.5">License token <span class="text-slate-400 font-normal">(required for service-based tools)</span></label>
                            <input type="text" id="license_token" name="license_token" value="{{ $licenseToken ?? '' }}" placeholder="Paste your license token" class="w-full px-4 py-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-all placeholder-slate-400 font-mono">
                        </div>
                        <div>
                            <label for="install_domain" class="block text-sm font-medium text-slate-700 mb-1.5">Install domain <span class="text-slate-400 font-normal">(optional, for domain-locked licenses)</span></label>
                            <input type="text" id="install_domain" name="install_domain" placeholder="example.com" class="w-full px-4 py-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-all placeholder-slate-400">
                        </div>
                        <div class="flex items-start gap-3">
                            <input type="checkbox" id="plain_install" name="plain_install" value="1" {{ old('plain_install') ? 'checked' : '' }} class="mt-1 h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <label for="plain_install" class="text-sm text-slate-700">
                                <span class="font-medium">Install as plain code</span> — no encryption. Raw PHP/CSS/JS sent to BD.
                            </label>
                        </div>
                        <div class="flex items-start gap-3">
                            <input type="checkbox" id="enforce_license" name="enforce_license" value="1" {{ old('enforce_license') ? 'checked' : '' }} class="mt-1 h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <label for="enforce_license" class="text-sm text-slate-700">
                                <span class="font-medium">Enforce license</span> — only when plain code is used. Widget will check license at runtime; if invalid or expired, show a renewal message instead of the tool.
                            </label>
                        </div>
                        <p class="text-xs text-slate-500 -mt-1">If you use Enforce license: set <code class="bg-slate-100 px-1 rounded">APP_URL</code> in your installer <code class="bg-slate-100 px-1 rounded">.env</code> to a <strong>public URL</strong> (e.g. <code class="bg-slate-100 px-1 rounded">https://your-installer.example.com</code>). localhost will not work — the installed site cannot reach it.</p>
                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-3 text-sm font-medium text-white bg-slate-800 rounded-xl hover:bg-slate-900 focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition-all shadow-sm hover:shadow">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Install
                        </button>
                        @if(($toolSlug ?? '') === 'faq')
                        <div class="mt-4 p-4 rounded-xl bg-slate-100 border border-slate-200 text-sm text-slate-700">
                            <p class="font-medium mb-1">FAQ: Updating code &amp; admin panel</p>
                            <ul class="list-disc list-inside space-y-0.5 text-slate-600">
                                <li><strong>New code not showing?</strong> After editing <code class="bg-slate-200 px-1 rounded">plugin-assets/</code>, run <code class="bg-slate-200 px-1 rounded">php artisan tools:encrypt faq</code> then run Install (or Update existing) here so the new payload is sent to BD.</li>
                                <li><strong>Works on front but not in admin?</strong> Set the license token so it is available in admin too: e.g. <code class="bg-slate-200 px-1 rounded">define('FAQ_LICENSE_TOKEN', 'your-token');</code> or <code class="bg-slate-200 px-1 rounded">$GLOBALS['faq_license_token'] = 'your-token';</code> in a file that loads for both front and admin (e.g. theme functions or global include).</li>
                            </ul>
                        </div>
                        @endif
                    </form>
                </div>
            </section>

            <p class="mt-10 text-center text-sm text-slate-500">
                <a href="{{ route('admin.dashboard') }}" class="text-sky-500 hover:underline font-medium">Dashboard</a>
                <span class="mx-2">·</span>
                <a href="{{ route('admin.licenses.index') }}" class="text-sky-500 hover:underline font-medium">Licenses</a>
            </p>
        </main>
    </div>
    <script>
    (function () {
        var form = document.getElementById('install-run-form');
        var loader = document.getElementById('install-loader');
        var progressFill = document.getElementById('install-progress-fill');
        var loaderPct = document.getElementById('install-loader-pct');
        var loaderText = document.getElementById('install-loader-text');
        if (!form || !loader) return;
        form.addEventListener('submit', function () {
            loader.classList.add('show');
            loader.setAttribute('aria-hidden', 'false');
            progressFill.style.width = '0%';
            loaderPct.textContent = '0%';
            loaderText.textContent = 'Installing...';
            var pct = 0;
            var intervalId = setInterval(function () {
                if (pct >= 95) return;
                pct += Math.random() * 6 + 3;
                if (pct > 95) pct = 95;
                progressFill.style.width = pct + '%';
                loaderPct.textContent = Math.round(pct) + '%';
            }, 350);
        });
    })();
    </script>
</body>
</html>
