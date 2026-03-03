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
<body class="font-sans antialiased text-slate-700 bg-slate-100 min-h-screen">
    <div id="install-loader" aria-hidden="true">
        <div class="flex items-center gap-4">
            <p id="install-loader-text" class="text-white font-medium text-sm whitespace-nowrap">Installing...</p>
            <div class="progress-bar flex-1 min-w-0"><div id="install-progress-fill" class="progress-fill"></div></div>
            <p id="install-loader-pct" class="text-white/90 text-sm font-medium tabular-nums">0%</p>
        </div>
    </div>
    <div class="min-h-screen flex flex-col">
        {{-- Header --}}
        <header class="bg-white border-b border-slate-200 shadow-sm sticky top-0 z-10">
            <div class="max-w-[1600px] mx-auto px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-sky-500 to-sky-600 flex items-center justify-center text-white shadow-lg shadow-sky-500/25">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-slate-900">Tool Installer</h1>
                        <p class="text-sm text-slate-500">Install tools to your Brilliant Directories site</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.dashboard') }}" class="text-sm font-medium text-slate-600 hover:text-sky-600 transition-colors">Dashboard</a>
                    <a href="{{ route('admin.licenses.index') }}" class="text-sm font-medium text-slate-600 hover:text-sky-600 transition-colors">Licenses</a>
                </div>
            </div>
        </header>

        <main class="flex-1 w-full max-w-[1600px] mx-auto px-6 py-8">
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
                        @if(old('plain_install'))<input type="hidden" name="plain_install" value="1">@endif
                        @if(old('enforce_license'))<input type="hidden" name="enforce_license" value="1">@endif
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

            {{-- Main Content Grid --}}
            <div class="grid grid-cols-12 gap-6">
                
                {{-- Left Sidebar: Steps 1 & 1.5 --}}
                <div class="col-span-12 lg:col-span-4 space-y-6">
                    {{-- Step 1: Connect BD --}}
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-sky-500 to-sky-600">
                            <div class="flex items-center gap-3">
                                <span class="flex items-center justify-center w-8 h-8 rounded-full bg-white text-sky-600 text-sm font-bold">1</span>
                                <div>
                                    <h2 class="text-base font-bold text-white">Connect BD Site</h2>
                                    <p class="text-xs text-sky-100">Verify your API token</p>
                                </div>
                            </div>
                        </div>
                        <form method="post" action="{{ route('admin.install.verify') }}" class="p-6 space-y-4">
                            @csrf
                            <div>
                                <label for="bd_base_url" class="block text-sm font-semibold text-slate-700 mb-2">BD Base URL</label>
                                <input type="url" id="bd_base_url" name="bd_base_url" value="{{ $bdBaseUrl }}" placeholder="https://yoursite.directoryup.com" required autocomplete="url" class="w-full px-4 py-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-all placeholder-slate-400 bg-slate-50">
                            </div>
                            <div>
                                <label for="bd_api_key" class="block text-sm font-semibold text-slate-700 mb-2">BD API Key</label>
                                <input type="password" id="bd_api_key" name="bd_api_key" value="{{ $bdApiKey ?? '' }}" placeholder="Your BD API key" required autocomplete="off" class="w-full px-4 py-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-all placeholder-slate-400 bg-slate-50">
                            </div>
                            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 text-sm font-semibold text-white bg-sky-500 rounded-xl hover:bg-sky-600 focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transition-all shadow-md hover:shadow-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                Verify Token
                            </button>
                        </form>
                    </div>

                    {{-- Step 1.5: Server Files --}}
                    @if(!empty($supportsServerFetch))
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-violet-500 to-violet-600">
                            <div class="flex items-center gap-3">
                                <span class="flex items-center justify-center w-8 h-8 rounded-full bg-white text-violet-600 text-sm font-bold">1.5</span>
                                <div>
                                    <h2 class="text-base font-bold text-white">Server Files</h2>
                                    <p class="text-xs text-violet-100">Upload tool assets</p>
                                </div>
                            </div>
                        </div>
                        <form method="post" action="{{ route('admin.install.setup') }}" enctype="multipart/form-data" class="p-6 space-y-4">
                            @csrf
                            <input type="hidden" name="tool_slug" value="{{ $toolSlug }}">
                            <div>
                                <label for="server_files" class="block text-sm font-semibold text-slate-700 mb-2">Server Files</label>
                                <input type="file" id="server_files" name="server_files[]" multiple accept=".html,.htm,.js,.css" class="w-full px-4 py-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-violet-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100 bg-slate-50">
                                <p class="text-xs text-slate-500 mt-1.5">HTML, JS, CSS files to serve from your server</p>
                            </div>
                            <div>
                                <label for="custom_base_url" class="block text-sm font-semibold text-slate-700 mb-2">Custom Base URL <span class="text-slate-400 font-normal">(optional)</span></label>
                                <input type="url" id="custom_base_url" name="custom_base_url" value="{{ old('custom_base_url') }}" placeholder="https://cdn.example.com/" class="w-full px-4 py-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all placeholder-slate-400 bg-slate-50">
                            </div>
                            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 text-sm font-semibold text-white bg-violet-500 rounded-xl hover:bg-violet-600 focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 transition-all shadow-md">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                Save Setup
                            </button>
                        </form>
                    </div>
                    @endif
                </div>

                {{-- Right Main: Step 2 --}}
                <div class="col-span-12 lg:col-span-8">
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden h-full">
                        <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-800 to-slate-900">
                            <div class="flex items-center gap-3">
                                <span class="flex items-center justify-center w-8 h-8 rounded-full bg-white text-slate-800 text-sm font-bold">2</span>
                                <div>
                                    <h2 class="text-base font-bold text-white">Install Tool</h2>
                                    <p class="text-xs text-slate-400">Select a tool and install to your BD site</p>
                                </div>
                            </div>
                        </div>
                        <form id="install-run-form" method="post" action="{{ route('admin.install.run') }}" class="p-6 space-y-6">
                            @csrf
                            <input type="hidden" name="bd_base_url" value="{{ $bdBaseUrl }}">
                            <input type="hidden" name="bd_api_key" value="{{ $bdApiKey ?? '' }}">
                            
                            {{-- Tool Selection --}}
                            <div>
                                <label for="tool_slug" class="block text-sm font-semibold text-slate-700 mb-2">Select Tool</label>
                                <select id="tool_slug" name="tool_slug" required class="w-full px-4 py-3.5 text-base border border-slate-300 rounded-xl focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-all bg-slate-50">
                                    @foreach($tools as $slug => $config)
                                        <option value="{{ $slug }}" data-help='{{ ($config['help_text'] ?? '') }}' {{ ($toolSlug ?? '') === $slug ? 'selected' : '' }}>{{ $config['name'] ?? $slug }} ({{ $config['type'] ?? 'service' }})</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- License & Domain Row --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="license_token" class="block text-sm font-semibold text-slate-700 mb-2">License Token <span class="text-red-500">*</span></label>
                                    <input type="text" id="license_token" name="license_token" value="{{ $licenseToken ?? '' }}" placeholder="Paste your license token" required class="w-full px-4 py-3.5 text-base border border-slate-300 rounded-xl focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-all placeholder-slate-400 bg-slate-50 font-mono">
                                </div>
                                <div>
                                    <label for="install_domain" class="block text-sm font-semibold text-slate-700 mb-2">Install Domain <span class="text-slate-400 font-normal">(optional)</span></label>
                                    <input type="text" id="install_domain" name="install_domain" placeholder="example.com" class="w-full px-4 py-3.5 text-base border border-slate-300 rounded-xl focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-all placeholder-slate-400 bg-slate-50">
                                </div>
                            </div>

                            {{-- Options --}}
                            <div class="bg-slate-50 rounded-xl p-5 space-y-4">
                                <div class="flex items-start gap-3">
                                    <input type="checkbox" id="plain_install" name="plain_install" value="1" {{ old('plain_install') ? 'checked' : '' }} class="mt-1 h-5 w-5 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                    <label for="plain_install" class="text-sm text-slate-700">
                                        <span class="font-semibold">Install as plain code</span>
                                        <p class="text-xs text-slate-500 mt-0.5">No encryption. Raw PHP/CSS/JS sent to BD. Easier to debug.</p>
                                    </label>
                                </div>
                                <div class="flex items-start gap-3">
                                    <input type="checkbox" id="enforce_license" name="enforce_license" value="1" {{ old('enforce_license') ? 'checked' : '' }} class="mt-1 h-5 w-5 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                    <label for="enforce_license" class="text-sm text-slate-700">
                                        <span class="font-semibold">Enforce license at runtime</span>
                                        <p class="text-xs text-slate-500 mt-0.5">Widget checks license on each page load. Shows renewal message if invalid/expired.</p>
                                    </label>
                                </div>
                            </div>

                            <p class="text-xs text-slate-500 bg-amber-50 border border-amber-200 p-3 rounded-lg">⚠️ <strong>Note:</strong> For "Enforce license", set <code class="bg-amber-100 px-1.5 py-0.5 rounded text-amber-800">APP_URL</code> in your <code class="bg-amber-100 px-1.5 py-0.5 rounded text-amber-800">.env</code> to a <strong>public URL</strong> (not localhost).</p>

                            <button type="submit" class="w-full inline-flex items-center justify-center gap-3 px-6 py-4 text-base font-semibold text-white bg-gradient-to-r from-slate-800 to-slate-900 rounded-xl hover:from-slate-900 hover:to-slate-800 focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition-all shadow-lg hover:shadow-xl">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                Install Tool
                            </button>

                            {{-- Help Text --}}
                            <div id="tool-help-text" class="hidden p-5 rounded-xl bg-gradient-to-br from-sky-50 to-blue-50 border border-sky-200">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
    (function () {
        var toolSelect = document.getElementById('tool_slug');
        var helpTextContainer = document.getElementById('tool-help-text');
        if (toolSelect && helpTextContainer) {
            toolSelect.addEventListener('change', function() {
                var selectedOption = toolSelect.options[toolSelect.selectedIndex];
                var helpText = selectedOption.getAttribute('data-help');
                if (helpText && helpText.trim() !== '') {
                    helpTextContainer.innerHTML = helpText;
                    helpTextContainer.classList.remove('hidden');
                } else {
                    helpTextContainer.classList.add('hidden');
                }
            });
            var initialOption = toolSelect.options[toolSelect.selectedIndex];
            var initialHelp = initialOption.getAttribute('data-help');
            if (!initialHelp || initialHelp.trim() === '') {
                helpTextContainer.classList.add('hidden');
            }
        }
        
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
                pct += 5;
                if (pct > 90) { clearInterval(intervalId); return; }
                progressFill.style.width = pct + '%';
                loaderPct.textContent = pct + '%';
            }, 150);
            window.onbeforeunload = function() { clearInterval(intervalId); };
        });
    })();
    </script>
</body>
</html>
