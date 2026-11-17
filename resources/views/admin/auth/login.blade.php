<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In - AppointEase Admin</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-slate-900 flex items-center justify-center p-4 selection:bg-slate-900 selection:text-white">
    <div class="max-w-sm w-full bg-white rounded-lg border border-slate-200 p-6 sm:p-8">
        <!-- Logo / Brand -->
        <div class="mb-6">
            <div class="w-8 h-8 rounded-md bg-slate-900 text-white flex items-center justify-center text-xs font-semibold mb-3">
                AE
            </div>
            <h1 class="text-xl font-semibold text-slate-900 tracking-tight">Staff sign in</h1>
            <p class="text-xs text-slate-500 mt-0.5">Enter your credentials to manage appointments.</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 p-2.5 rounded-md bg-rose-50 border border-rose-200 text-xs text-rose-800">
                {{ $errors->first() }}
            </div>
        @endif

        @if (session('success'))
            <div class="mb-4 p-2.5 rounded-md bg-emerald-50 border border-emerald-200 text-xs text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-3.5">
            @csrf

            <div>
                <label for="email" class="block text-xs font-medium text-slate-700 mb-1">Email address</label>
                <input
                    type="email"
                    name="email"
                    id="email"
                    value="{{ old('email', 'admin@example.com') }}"
                    required
                    class="w-full text-sm rounded-md border border-slate-200 px-3 py-2 text-slate-800 placeholder-slate-400 focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 transition-colors"
                >
            </div>

            <div>
                <label for="password" class="block text-xs font-medium text-slate-700 mb-1">Password</label>
                <input
                    type="password"
                    name="password"
                    id="password"
                    value="password"
                    required
                    class="w-full text-sm rounded-md border border-slate-200 px-3 py-2 text-slate-800 placeholder-slate-400 focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 transition-colors"
                >
            </div>

            <div class="flex items-center justify-between pt-0.5">
                <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                    <span>Remember me</span>
                </label>
            </div>

            <button
                type="submit"
                class="w-full py-2 px-4 rounded-md text-sm font-medium text-white bg-slate-900 hover:bg-slate-800 transition-colors cursor-pointer"
            >
                Sign in
            </button>
        </form>

        <!-- Demo Credentials Helper -->
        <div class="mt-6 pt-5 border-t border-slate-100 text-xs">
            <div class="flex items-center justify-between text-slate-500 mb-1.5">
                <span class="text-[11px]">Demo account</span>
                <button
                    type="button"
                    onclick="document.getElementById('email').value='admin@example.com'; document.getElementById('password').value='password';"
                    class="text-[11px] font-medium text-slate-900 hover:underline"
                >
                    Prefill
                </button>
            </div>
            <div class="bg-slate-50 border border-slate-200 rounded p-2 text-[11px] font-mono text-slate-600">
                admin@example.com / password
            </div>

            <div class="mt-4 text-center">
                <a href="{{ route('booking.index') }}" class="text-xs text-slate-500 hover:text-slate-900 transition-colors">
                    &larr; Back to customer booking
                </a>
            </div>
        </div>
    </div>
</body>
</html>
