<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Book an Appointment' }} - {{ config('app.name', 'AppointEase') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-slate-900 flex flex-col justify-between selection:bg-slate-900 selection:text-white">
    <!-- Navigation Header -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-40">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-between h-14">
                <a href="{{ route('booking.index') }}" class="flex items-center gap-2.5">
                    <span class="w-7 h-7 rounded-md bg-slate-900 text-white flex items-center justify-center text-xs font-semibold">
                        AE
                    </span>
                    <span class="text-sm font-semibold tracking-tight text-slate-900">AppointEase</span>
                </a>

                <div class="flex items-center gap-4 text-xs font-medium">
                    <a href="{{ route('booking.lookup') }}" class="text-slate-600 hover:text-slate-900 transition-colors">
                        Find booking
                    </a>

                    @auth
                        <a href="{{ route('admin.dashboard') }}" class="px-2.5 py-1.5 rounded-md bg-slate-900 text-white hover:bg-slate-800 transition-colors">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('admin.login') }}" class="text-slate-500 hover:text-slate-900 transition-colors">
                            Staff login
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow">
        <!-- Flash Messages -->
        <div class="max-w-4xl mx-auto px-4 sm:px-6 pt-4">
            @if (session('success'))
                <div class="mb-4 rounded-md bg-emerald-50 border border-emerald-200 p-3 text-xs text-emerald-800 flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 rounded-md bg-rose-50 border border-rose-200 p-3 text-xs text-rose-800 flex items-center gap-2">
                    <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-md bg-rose-50 border border-rose-200 p-3 text-xs text-rose-800">
                    <div class="font-medium mb-1">Please check the form:</div>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-200 bg-white py-6 mt-16 text-xs text-slate-500">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p>&copy; {{ date('Y') }} AppointEase</p>
            <div class="flex items-center gap-4">
                <a href="{{ route('booking.index') }}" class="hover:text-slate-900 transition-colors">Book</a>
                <a href="{{ route('booking.lookup') }}" class="hover:text-slate-900 transition-colors">Find booking</a>
                <a href="{{ route('admin.login') }}" class="hover:text-slate-900 transition-colors">Staff</a>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
