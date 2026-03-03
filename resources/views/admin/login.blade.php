<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login – Tool Installer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['DM Sans', 'system-ui', 'sans-serif'] }, animation: { 'fade-in': 'fadeIn 0.4s ease-out', 'slide-up': 'slideUp 0.45s ease-out' }, keyframes: { fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } }, slideUp: { '0%': { opacity: '0', transform: 'translateY(16px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } } } } } };
    </script>
</head>
<body class="font-sans antialiased text-slate-700 bg-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-lg border border-slate-200 p-8 animate-slide-up">
        <h1 class="text-xl font-bold text-slate-900 mb-6">Admin login</h1>
        @if(session('error'))
            <div class="mb-4 p-3.5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm animate-fade-in" role="alert">{{ session('error') }}</div>
        @endif
        <form method="post" action="{{ route('admin.login.post') }}" class="space-y-4">
            @csrf
            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                <input type="password" id="password" name="password" required autofocus placeholder="Admin password" autocomplete="current-password" class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-shadow">
            </div>
            <button type="submit" class="w-full py-2.5 text-sm font-medium text-white bg-sky-500 rounded-lg hover:bg-sky-600 focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transition-all shadow-sm">Log in</button>
        </form>
        <p class="mt-5 text-center text-slate-500 text-sm">Internal use only. Installer is available after login.</p>
    </div>
</body>
</html>
