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
                    },
                    keyframes: {
                        fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                        slideUp: { '0%': { opacity: '0', transform: 'translateY(20px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                    },
                },
            },
        };
    </script>
    <style>
        body { font-family: 'DM Sans', sans-serif; }
        
        /* Step Progress */
        .step-progress-item { transition: all 0.3s ease; }
        .step-progress-item.active .step-circle {
            background: linear-gradient(135deg, #0ea5e9, #3b82f6);
            box-shadow: 0 0 20px rgba(14, 165, 233, 0.5);
        }
        .step-progress-item.completed .step-circle {
            background: linear-gradient(135deg, #10b981, #34d399);
        }
        .step-progress-item.completed .step-check { display: flex; }
        .step-progress-item.completed .step-number { display: none; }
        .step-check { display: none; }
        
        /* Step Content */
        .step-panel { display: none; }
        .step-panel.active { display: block; animation: fadeIn 0.4s ease-out; }
        
        /* Step-by-step Loading Animation from https://codepen.io/jkantner/pen/VwoOERb */
        .loading-steps {
            position: relative;
            height: 280px;
            max-width: 340px;
            margin: 0 auto;
            overflow: hidden;
        }
        .loading-step {
            display: flex;
            align-items: center;
            gap: 1em;
            padding: 0 1.5em;
            position: absolute;
            top: 0;
            left: 0;
            height: 3.5em;
            width: 100%;
            transition: opacity 0.3s, transform 0.3s cubic-bezier(0.65, 0, 0.35, 1);
            opacity: 0;
        }
        .loading-step.in {
            opacity: 1;
        }
        .loading-step-icon {
            width: 1.5em;
            height: 1.5em;
            flex-shrink: 0;
        }
        .loading-step-icon--waiting {
            color: #9ca3af;
        }
        .loading-step-icon--progress {
            color: #f59e0b;
        }
        .loading-step-icon--done {
            color: #10b981;
        }
        .loading-step-title {
            font-size: 1.1em;
            font-weight: 500;
            line-height: 1.2;
            color: #1f2937;
        }
        .loading-step-info {
            font-size: 0.75em;
            line-height: 1.333;
            color: #6b7280;
            margin-top: 0.25em;
        }
        .loading-ellipsis {
            display: inline-flex;
        }
        .loading-ellipsis-dot {
            visibility: hidden;
            animation: ellipsis-dot-1 2s steps(1,end) infinite;
        }
        .loading-ellipsis-dot:nth-child(2) {
            animation-name: ellipsis-dot-2;
        }
        .loading-ellipsis-dot:nth-child(3) {
            animation-name: ellipsis-dot-3;
        }
        @keyframes ellipsis-dot-1 {
            from { visibility: hidden; }
            25%, to { visibility: visible; }
        }
        @keyframes ellipsis-dot-2 {
            from, 25% { visibility: hidden; }
            50%, to { visibility: visible; }
        }
        @keyframes ellipsis-dot-3 {
            from, 50% { visibility: hidden; }
            75%, to { visibility: visible; }
        }
        /* Loading step content */
        .loading-step-content {
            text-align: left;
        }
        /* Hidden loader container */
        .loader-container { display: none; }
        
        /* Line-style Loader - rainbow horizontal lines */
        .line-loader-container {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            display: none;
        }
        .line-loader-container.show {
            display: block;
        }
        .line-loader {
            height: 100%;
            width: 100%;
            background: linear-gradient(
                90deg,
                #f59e0b 0%,
                #10b981 20%,
                #3b82f6 40%,
                #8b5cf6 60%,
                #ef4444 80%,
                #0ea5e9 100%
            );
            background-size: 200% 100%;
            animation: lineMove 1.5s linear infinite;
            border-radius: 4px;
        }
        @keyframes lineMove {
            0% { background-position: 100% 0; }
            100% { background-position: -100% 0; }
        }
        
        /* Progress Bar */
        .progress-line { height: 4px; border-radius: 2px; background: #e2e8f0; overflow: hidden; }
        .progress-line-fill { height: 100%; background: linear-gradient(90deg, #0ea5e9, #8b5cf6); transition: width 0.5s ease; }
        
        /* Form */
        .form-input { width: 100%; padding: 0.75rem 1rem; font-size: 0.875rem; border: 1px solid #cbd5e1; border-radius: 0.75rem; background: #f8fafc; transition: all 0.2s; }
        .form-input:focus { outline: none; border-color: #0ea5e9; box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1); }
        .form-label { display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.5rem; }
        .form-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1.5rem; font-size: 0.875rem; font-weight: 600; border-radius: 0.75rem; transition: all 0.2s; }
        .form-btn-primary { background: linear-gradient(135deg, #0ea5e9, #3b82f6); color: white; }
        .form-btn-primary:hover { background: linear-gradient(135deg, #0284c7, #2563eb); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3); }
        
        /* Result */
        .result-item { opacity: 0; animation: slideUp 0.35s ease-out forwards; }
        .result-item:nth-child(1) { animation-delay: 0.1s; }
        .result-item:nth-child(2) { animation-delay: 0.2s; }
        .result-item:nth-child(3) { animation-delay: 0.3s; }
        .result-item:nth-child(4) { animation-delay: 0.4s; }
        .result-item:nth-child(5) { animation-delay: 0.5s; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-100 via-slate-50 to-blue-50" data-bd-connected="{{ $bdConnected ? '1' : '0' }}">
    <div class="min-h-screen">
        <header class="bg-white border-b border-slate-200 shadow-sm">
            <div class="max-w-4xl mx-auto px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-sky-500 to-sky-600 flex items-center justify-center text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-slate-900">Tool Installer</h1>
                        <p class="text-xs text-slate-500">Install tools to Brilliant Directories</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.dashboard') }}" class="text-sm text-slate-600 hover:text-sky-600">Dashboard</a>
                    <a href="{{ route('admin.licenses.index') }}" class="text-sm text-slate-600 hover:text-sky-600">Licenses</a>
                </div>
            </div>
        </header>

        <main class="max-w-4xl mx-auto px-6 py-8">
            @if(session('success'))
                <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center gap-3 animate-fade-in">
                    <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <p class="text-sm text-emerald-800">{{ session('success') }}</p>
                </div>
            @endif
            @if(session('error'))
                <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 flex items-center gap-3 animate-fade-in">
                    <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </div>
                    <p class="text-sm text-red-800">{{ session('error') }}</p>
                </div>
            @endif
            @if(session('warning'))
                <div class="mb-6 p-4 rounded-xl bg-amber-50 border border-amber-200 flex items-center gap-3 animate-fade-in">
                    <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/></svg>
                    </div>
                    <p class="text-sm text-amber-800">{{ session('warning') }}</p>
                </div>
            @endif

            @if(!$installSuccess)
                <!-- Main Installer Card - Only show when NOT successful -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xl overflow-hidden">
                <div class="bg-gradient-to-r from-slate-800 to-slate-900 px-6 py-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold text-white">Install Tool</h2>
                        <span class="text-sm text-sky-400" id="step-label">Step 1 of 3</span>
                    </div>
                    <div class="progress-line mb-5">
                        <div class="progress-line-fill" id="progress-bar" style="width: 33%"></div>
                    </div>
                    <div class="flex items-center justify-between">
                        <button type="button" onclick="goToStep(1)" class="step-progress-item flex items-center gap-2" id="step-indicator-1">
                            <div class="step-circle w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
                                <span class="step-number text-white text-sm font-bold">1</span>
                                <span class="step-check text-white"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></span>
                            </div>
                            <span class="text-white text-sm font-medium">Connect</span>
                        </button>
                        <button type="button" onclick="goToStep(2)" class="step-progress-item flex items-center gap-2" id="step-indicator-2">
                            <div class="step-circle w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
                                <span class="step-number text-white text-sm font-bold">2</span>
                                <span class="step-check text-white"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></span>
                            </div>
                            <span class="text-white/70 text-sm font-medium">Configure</span>
                        </button>
                        <button type="button" onclick="goToStep(3)" class="step-progress-item flex items-center gap-2" id="step-indicator-3">
                            <div class="step-circle w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
                                <span class="step-number text-white text-sm font-bold">3</span>
                                <span class="step-check text-white"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></span>
                            </div>
                            <span class="text-white/70 text-sm font-medium">Install</span>
                        </button>
                    </div>
                </div>

                <div id="step-panel-1" class="step-panel active p-6">
                    <div class="max-w-md mx-auto">
                        <div class="text-center mb-6">
                            <div class="w-14 h-14 mx-auto rounded-2xl bg-sky-100 flex items-center justify-center mb-3">
                                <svg class="w-7 h-7 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-slate-900">Connect to Brilliant Directories</h3>
                            <p class="text-sm text-slate-500">Enter your BD site URL and API key</p>
                        </div>
                        <form method="post" action="{{ route('admin.install.verify') }}" class="space-y-4">
                            @csrf
                            <div>
                                <label class="form-label">BD Base URL</label>
                                <input type="url" name="bd_base_url" value="{{ $bdBaseUrl }}" placeholder="https://yoursite.directoryup.com" required class="form-input">
                            </div>
                            <div>
                                <label class="form-label">BD API Key</label>
                                <input type="password" name="bd_api_key" value="{{ $bdApiKey ?? '' }}" placeholder="Your BD API key" required class="form-input">
                            </div>
                            <button type="submit" class="form-btn form-btn-primary w-full">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                Verify Connection
                            </button>
                        </form>
                        @if($bdBaseUrl)
                        <div class="mt-4 p-3 rounded-lg bg-emerald-50 border border-emerald-200 flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span class="text-sm text-emerald-700">Connected to {{ $bdBaseUrl }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <div id="step-panel-2" class="step-panel p-6">
                    <div class="max-w-lg mx-auto">
                        <div class="text-center mb-6">
                            <div class="w-14 h-14 mx-auto rounded-2xl bg-violet-100 flex items-center justify-center mb-3">
                                <svg class="w-7 h-7 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-slate-900">Configure Your Tool</h3>
                            <p class="text-sm text-slate-500">Select tool and enter license details</p>
                        </div>
                        <form id="install-form" method="post" action="{{ route('admin.install.run') }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="bd_base_url" value="{{ $bdBaseUrl }}">
                            <input type="hidden" name="bd_api_key" value="{{ $bdApiKey ?? '' }}">
                            <div>
                                <label class="form-label">Select Tool</label>
                                <select name="tool_slug" required class="form-input">
                                    @foreach($tools as $slug => $config)
                                        <option value="{{ $slug }}" {{ ($toolSlug ?? '') === $slug ? 'selected' : '' }}>{{ $config['name'] ?? $slug }} ({{ $config['type'] ?? 'service' }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="form-label">License Token <span class="text-red-500">*</span></label>
                                    <input type="text" name="license_token" value="{{ $licenseToken ?? '' }}" placeholder="Paste your license token" required class="form-input font-mono">
                                </div>
                                <div>
                                    <label class="form-label">Install Domain <span class="text-red-500">*</span></label>
                                    <input type="text" name="install_domain" placeholder="example.com" required class="form-input">
                                    <p class="text-xs text-slate-500 mt-1">License bound to this domain</p>
                                </div>
                            </div>
                            <div class="bg-slate-50 rounded-xl p-4 space-y-3">
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" name="plain_install" value="1" {{ old('plain_install') ? 'checked' : '' }} class="mt-1 w-4 h-4 rounded border-slate-300 text-sky-600">
                                    <span class="text-sm">
                                        <span class="font-medium">Install as plain code</span>
                                        <p class="text-xs text-slate-500">No encryption. Easier to debug.</p>
                                    </span>
                                </label>
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" name="enforce_license" value="1" {{ old('enforce_license') ? 'checked' : '' }} class="mt-1 w-4 h-4 rounded border-slate-300 text-sky-600">
                                    <span class="text-sm">
                                        <span class="font-medium">Enforce license at runtime</span>
                                        <p class="text-xs text-slate-500">Check license on each page load</p>
                                    </span>
                                </label>
                            </div>
                            <p class="text-xs text-slate-500 bg-amber-50 border border-amber-200 p-2 rounded-lg">⚠️ Set APP_URL in .env to a public URL for license enforcement.</p>
                        </form>
                    </div>
                </div>

                <div id="step-panel-3" class="step-panel p-6">
                    <div class="text-center py-4">
                        <!-- Step-by-step Loading Animation (hidden initially) -->
                        <div class="loading-steps hidden" id="loading-steps">
                            <!-- Step 1: Preparing -->
                            <div class="loading-step" data-step="0">
                                <svg class="loading-step-icon loading-step-icon--waiting" viewBox="0 0 16 16">
                                    <g fill="currentColor" transform="translate(8,8)">
                                        <rect x="-1" width="2" height="2" transform="rotate(0) translate(0,-6)" />
                                        <rect x="-1" width="2" height="2" transform="rotate(22.5) translate(0,-6)" />
                                        <rect x="-1" width="2" height="2" transform="rotate(45) translate(0,-6)" />
                                        <rect x="-1" width="2" height="2" transform="rotate(67.5) translate(0,-6)" />
                                        <rect x="-1" width="2" height="2" transform="rotate(90) translate(0,-6)" />
                                        <rect x="-1" width="2" height="2" transform="rotate(112.5) translate(0,-6)" />
                                        <rect x="-1" width="2" height="2" transform="rotate(135) translate(0,-6)" />
                                        <rect x="-1" width="2" height="2" transform="rotate(157.5) translate(0,-6)" />
                                        <rect x="-1" width="2" height="2" transform="rotate(180) translate(0,-6)" />
                                        <rect x="-1" width="2" height="2" transform="rotate(202.5) translate(0,-6)" />
                                        <rect x="-1" width="2" height="2" transform="rotate(225) translate(0,-6)" />
                                        <rect x="-1" width="2" height="2" transform="rotate(247.5) translate(0,-6)" />
                                        <rect x="-1" width="2" height="2" transform="rotate(270) translate(0,-6)" />
                                        <rect x="-1" width="2" height="2" transform="rotate(292.5) translate(0,-6)" />
                                        <rect x="-1" width="2" height="2" transform="rotate(315) translate(0,-6)" />
                                        <rect x="-1" width="2" height="2" transform="rotate(337.5) translate(0,-6)" />
                                    </g>
                                </svg>
                                <div class="loading-step-content">
                                    <div class="loading-step-title">Preparing</div>
                                    <div class="loading-step-info">Gathering tool assets...</div>
                                </div>
                            </div>
                            <!-- Step 2: Downloading -->
                            <div class="loading-step" data-step="1">
                                <svg class="loading-step-icon loading-step-icon--progress" viewBox="0 0 16 16">
                                    <defs><clipPath id="half-clip"><rect x="8" y="0" width="8" height="16"/></clipPath></defs>
                                    <circle fill="none" stroke="currentColor" stroke-width="2" cx="8" cy="8" r="7" />
                                    <circle fill="currentColor" cx="8" cy="8" r="5" clip-path="url(#half-clip)" />
                                </svg>
                                <div class="loading-step-content">
                                    <div class="loading-step-title">Connecting to BD</div>
                                    <div class="loading-step-info">
                                        <span class="loading-ellipsis">
                                            <span class="loading-ellipsis-dot">.</span>
                                            <span class="loading-ellipsis-dot">.</span>
                                            <span class="loading-ellipsis-dot">.</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <!-- Step 3: Analyzing -->
                            <div class="loading-step" data-step="2">
                                <svg class="loading-step-icon loading-step-icon--progress" viewBox="0 0 16 16">
                                    <defs><clipPath id="half-clip-2"><rect x="8" y="0" width="8" height="16"/></clipPath></defs>
                                    <circle fill="none" stroke="currentColor" stroke-width="2" cx="8" cy="8" r="7" />
                                    <circle fill="currentColor" cx="8" cy="8" r="5" clip-path="url(#half-clip-2)" />
                                </svg>
                                <div class="loading-step-content">
                                    <div class="loading-step-title">Validating License</div>
                                    <div class="loading-step-info">
                                        <span class="loading-ellipsis">
                                            <span class="loading-ellipsis-dot">.</span>
                                            <span class="loading-ellipsis-dot">.</span>
                                            <span class="loading-ellipsis-dot">.</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <!-- Step 4: Creating -->
                            <div class="loading-step" data-step="3">
                                <svg class="loading-step-icon loading-step-icon--progress" viewBox="0 0 16 16">
                                    <defs><clipPath id="half-clip-3"><rect x="8" y="0" width="8" height="16"/></clipPath></defs>
                                    <circle fill="none" stroke="currentColor" stroke-width="2" cx="8" cy="8" r="7" />
                                    <circle fill="currentColor" cx="8" cy="8" r="5" clip-path="url(#half-clip-3)" />
                                </svg>
                                <div class="loading-step-content">
                                    <div class="loading-step-title">Creating Widgets</div>
                                    <div class="loading-step-info">
                                        <span class="loading-ellipsis">
                                            <span class="loading-ellipsis-dot">.</span>
                                            <span class="loading-ellipsis-dot">.</span>
                                            <span class="loading-ellipsis-dot">.</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <!-- Step 5: Finalizing -->
                            <div class="loading-step" data-step="4">
                                <svg class="loading-step-icon loading-step-icon--done" viewBox="0 0 16 16">
                                    <circle fill="currentColor" cx="8" cy="8" r="8" />
                                    <polyline fill="none" stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" points="4 8,7 11,12 5" />
                                </svg>
                                <div class="loading-step-content">
                                    <div class="loading-step-title">Finalizing</div>
                                    <div class="loading-step-info">Complete!</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Line-style Loader Above Ready to Install -->
                        <div class="line-loader-container mb-6">
                            <div class="line-loader"></div>
                        </div>
                        
                        <div id="install-message">
                            <h3 class="text-lg font-bold text-slate-900 mb-2">Ready to Install</h3>
                            <p class="text-sm text-slate-500 mb-6">Click the button below to install your tool</p>
                            <button type="submit" form="install-form" class="form-btn form-btn-primary px-8 py-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                Install Now
                            </button>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                    <button type="button" id="prev-btn" onclick="prevStep()" class="hidden px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">← Back</button>
                    <div></div>
                    <button type="button" id="next-btn" onclick="nextStep()" class="px-6 py-2 text-sm font-semibold text-white bg-gradient-to-r from-sky-500 to-sky-600 rounded-xl hover:from-sky-600 hover:to-sky-500">Next Step →</button>
                </div>
            </div>
            <!-- End Main Installer Card -->
            @endif
            
            <!-- Success Message - Show when installation succeeded -->
            @if($installSuccess && !empty($installResults))
                <div class="mb-8 p-6 rounded-2xl bg-white border border-emerald-200 shadow-lg animate-fade-in">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 mx-auto rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center mb-3 shadow-lg">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h2 class="text-xl font-bold text-emerald-900">Installation Complete!</h2>
                        <p class="text-sm text-emerald-700">Your widgets have been installed successfully.</p>
                    </div>
                    <ul class="space-y-2 max-w-md mx-auto">
                        @foreach($installResults as $i => $r)
                            <li class="result-item flex items-center gap-3 text-sm text-emerald-800 bg-emerald-50 rounded-lg p-3">
                                @if($r['ok'])
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>{{ $r['widget'] }}{{ isset($r['widget_id']) && $r['widget_id'] ? ' (ID ' . $r['widget_id'] . ')' : '' }}</span>
                                @else
                                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01"/></svg>
                                    <span>{{ $r['widget'] }}: {{ $r['message'] ?? 'Failed' }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-6 text-center">
                        <a href="{{ route('admin.install.form') }}?fresh=1" class="form-btn form-btn-primary">Install Another Tool</a>
                    </div>
                </div>
            @endif
        </main>
    </div>

    <script>
    (function() {
        var currentStep = 1;
        var bdConnected = document.body.getAttribute('data-bd-connected') === '1';
        
        // Auto-advance to step 2 after successful verification
        var successMessage = document.querySelector('.bg-emerald-50');
        if (successMessage && bdConnected) {
            currentStep = 2;
        }
        
        function updateUI() {
            document.getElementById('progress-bar').style.width = (currentStep * 33.33) + '%';
            document.getElementById('step-label').textContent = 'Step ' + currentStep + ' of 3';
            
            for (var i = 1; i <= 3; i++) {
                var indicator = document.getElementById('step-indicator-' + i);
                indicator.classList.remove('active', 'completed');
                if (i < currentStep) {
                    indicator.classList.add('completed');
                } else if (i === currentStep) {
                    indicator.classList.add('active');
                }
            }
            
            for (var i = 1; i <= 3; i++) {
                var panel = document.getElementById('step-panel-' + i);
                if (i === currentStep) {
                    panel.classList.add('active');
                } else {
                    panel.classList.remove('active');
                }
            }
            
            var prevBtn = document.getElementById('prev-btn');
            var nextBtn = document.getElementById('next-btn');
            
            if (currentStep === 1) {
                prevBtn.classList.add('hidden');
            } else {
                prevBtn.classList.remove('hidden');
            }
            
            if (currentStep === 3) {
                nextBtn.classList.add('hidden');
            } else {
                nextBtn.classList.remove('hidden');
            }
        }
        
        function goToStep(step) {
            if (step === 2 && !bdConnected) {
                alert('Please verify your BD connection in Step 1 first');
                return;
            }
            currentStep = step;
            updateUI();
        }
        
        function nextStep() {
            if (currentStep < 3) {
                if (currentStep === 1 && !bdConnected) {
                    // Submit Step 1 form to verify connection
                    var step1Form = document.querySelector('#step-panel-1 form');
                    if (step1Form) {
                        step1Form.submit();
                        return;
                    }
                    alert('Please verify your BD connection first');
                    return;
                } else if (currentStep === 2) {
                    var form = document.getElementById('install-form');
                    var toolSlug = form.querySelector('[name="tool_slug"]').value;
                    var licenseToken = form.querySelector('[name="license_token"]').value;
                    var installDomain = form.querySelector('[name="install_domain"]').value;
                    
                    if (!toolSlug || !licenseToken || !installDomain) {
                        alert('Please fill in all required fields');
                        return;
                    }
                }
                currentStep++;
                updateUI();
            }
        }
        
        function prevStep() {
            if (currentStep > 1) {
                currentStep--;
                updateUI();
            }
        }
        
        // Form submission - animate step-by-step loading
        var installForm = document.getElementById('install-form');
        if (installForm) {
            installForm.addEventListener('submit', function() {
                var message = document.getElementById('install-message');
                var lineLoader = document.querySelector('.line-loader-container');
                var loadingSteps = document.getElementById('loading-steps');
                
                // Hide message, show line loader and step animation
                message.classList.add('hidden');
                lineLoader.classList.add('show');
                loadingSteps.classList.remove('hidden');
                
                // Animation step configuration
                var currentStep = 0;
                var loadingStepElements = loadingSteps.querySelectorAll('.loading-step');
                var totalSteps = loadingStepElements.length;
                
                function showStep(index) {
                    loadingStepElements.forEach(function(step, i) {
                        if (i < index) {
                            // Previous steps - move up and fade
                            step.classList.remove('in');
                            step.style.transform = 'translateY(' + (-(index - i) * 80) + '%)';
                            step.style.opacity = '0';
                        } else if (i === index) {
                            // Current step - show
                            step.classList.add('in');
                            step.style.transform = 'translateY(0)';
                            step.style.opacity = '1';
                        } else {
                            // Future steps - hide below
                            step.classList.remove('in');
                            step.style.transform = 'translateY(' + ((i - index) * 80) + '%)';
                            step.style.opacity = '0';
                        }
                    });
                }
                
                // Start animation
                showStep(0);
                
                // Progress through steps
                var delays = [800, 1200, 1200, 1200, 1000];
                var accumulatedDelay = 0;
                
                for (var i = 0; i < totalSteps; i++) {
                    accumulatedDelay += delays[i] || 1000;
                    setTimeout(function(stepIndex) {
                        showStep(stepIndex);
                    }, accumulatedDelay, i + 1);
                }
                
                window.onbeforeunload = function() {};
            });
        }
        
        window.goToStep = goToStep;
        window.nextStep = nextStep;
        window.prevStep = prevStep;
        
        updateUI();
    })();
    </script>
</body>
</html>
