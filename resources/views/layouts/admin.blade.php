<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') – Tool Installer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['DM Sans', 'system-ui', 'sans-serif'] },
                    animation: {
                        'fade-in': 'fadeIn 0.35s ease-out',
                        'slide-up': 'slideUp 0.4s ease-out',
                    },
                    keyframes: {
                        fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                        slideUp: { '0%': { opacity: '0', transform: 'translateY(12px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                    },
                },
            },
        };
    </script>
    <style>
        .admin-nav-link { display: flex; align-items: center; gap: 0.75rem; padding: 0.65rem 1rem; color: #94a3b8; font-weight: 500; text-decoration: none; border-radius: 0.5rem; transition: all 0.2s; }
        .admin-nav-link:hover { background: rgba(71,85,105,.5); color: #e2e8f0; }
        .admin-nav-link.active { background: #1e293b; color: #fff; }
    </style>
    @stack('styles')
</head>
<body class="font-sans text-slate-700 bg-slate-100 min-h-screen antialiased">
    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside class="w-64 flex-shrink-0 bg-slate-800 flex flex-col animate-fade-in">
            <div class="p-5 border-b border-white/10">
                <a href="{{ route('admin.dashboard') }}" class="text-lg font-bold text-white no-underline hover:text-sky-300 transition-colors">Tool Installer</a>
            </div>
            <nav class="flex-1 py-4 overflow-y-auto px-3">
                <a href="{{ route('admin.dashboard') }}" class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <svg class="w-5 h-5 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 18a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('admin.tools.index') }}" class="admin-nav-link {{ request()->routeIs('admin.tools.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    <span>Developer manual</span>
                </a>
                <a href="{{ route('admin.licenses.index') }}" class="admin-nav-link {{ request()->routeIs('admin.licenses.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                    <span>Licenses</span>
                </a>
                <span class="admin-nav-link opacity-60 cursor-not-allowed">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    <span>BDGS Orders</span>
                    <span class="ml-auto text-xs px-2 py-0.5 rounded-full bg-white/15">Soon</span>
                </span>
                <a href="{{ route('admin.install.form') }}" class="admin-nav-link {{ request()->routeIs('admin.install.*') && !request()->routeIs('admin.installation-history.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Installer</span>
                </a>
                <a href="{{ route('admin.installation-history.index') }}" class="admin-nav-link {{ request()->routeIs('admin.installation-history.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Installation history</span>
                </a>
                <span class="admin-nav-link opacity-60 cursor-not-allowed">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span>API &amp; Settings</span>
                    <span class="ml-auto text-xs px-2 py-0.5 rounded-full bg-white/15">Soon</span>
                </span>
            </nav>
        </aside>

        <main class="flex-1 flex flex-col min-w-0">
            <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between gap-4 flex-wrap animate-slide-up">
                <h1 class="text-xl font-semibold text-slate-900 m-0">@yield('heading', 'Admin')</h1>
                <div class="flex items-center gap-3">
                    @yield('top_actions')
                    <form method="post" action="{{ route('admin.logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-3 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 focus:ring-2 focus:ring-sky-500 focus:ring-offset-1 transition-colors">Log out</button>
                    </form>
                </div>
            </header>

            <div class="p-6 flex-1">
                @if(session('success'))
                    <div class="mb-4 p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm animate-slide-up" role="alert">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="mb-4 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm animate-slide-up" role="alert">{{ session('error') }}</div>
                @endif
                @if(isset($errors) && $errors->any())
                    <div class="mb-4 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm animate-slide-up" role="alert">
                        <p class="font-medium mb-1">Please fix the following:</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @yield('content')
            </div>
        </main>
    </div>
    @stack('scripts')
</body>
</html>
