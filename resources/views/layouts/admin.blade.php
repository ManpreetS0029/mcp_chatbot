<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Admin' }} - AppointEase</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-slate-900 flex flex-col selection:bg-slate-900 selection:text-white">
    <!-- Top Admin Header -->
    <header class="bg-slate-900 text-white sticky top-0 z-40 border-b border-slate-800">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-between h-14">
                <!-- Left brand & navigation -->
                <div class="flex items-center gap-6">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded bg-white text-slate-900 flex items-center justify-center text-xs font-bold">
                            AE
                        </span>
                        <span class="text-sm font-semibold tracking-tight text-white">AppointEase</span>
                        <span class="text-[11px] text-slate-400 font-normal">admin</span>
                    </a>

                    <nav class="hidden sm:flex items-center gap-1 text-xs">
                        <a href="{{ route('admin.dashboard') }}" class="px-2.5 py-1.5 rounded-md transition-colors {{ request()->routeIs('admin.dashboard*') || request()->routeIs('admin.appointments*') ? 'bg-slate-800 text-white font-medium' : 'text-slate-400 hover:text-white' }}">
                            Appointments
                        </a>
                        <a href="{{ route('admin.schedule.index') }}" class="px-2.5 py-1.5 rounded-md transition-colors {{ request()->routeIs('admin.schedule*') ? 'bg-slate-800 text-white font-medium' : 'text-slate-400 hover:text-white' }}">
                            Schedule &amp; Slots
                        </a>
                    </nav>
                </div>

                <!-- Right actions -->
                <div class="flex items-center gap-3 text-xs">
                    <a href="{{ route('booking.index') }}" target="_blank" class="hidden sm:inline-flex items-center gap-1 text-slate-400 hover:text-white transition-colors">
                        <span>View site</span>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                    </a>

                    <div class="flex items-center gap-2 pl-3 border-l border-slate-800">
                        <span class="text-slate-300 font-medium hidden md:inline">
                            {{ auth()->user()->name ?? 'Admin' }}
                        </span>

                        <form method="POST" action="{{ route('admin.logout') }}" class="inline">
                            @csrf
                            <button type="submit" title="Sign out" class="text-slate-400 hover:text-white p-1 rounded transition-colors cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Mobile subnav -->
            <div class="flex sm:hidden items-center gap-3 pb-2.5 pt-0.5 text-xs border-t border-slate-800">
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard*') ? 'text-white font-medium' : 'text-slate-400' }}">
                    Appointments
                </a>
                <a href="{{ route('admin.schedule.index') }}" class="{{ request()->routeIs('admin.schedule*') ? 'text-white font-medium' : 'text-slate-400' }}">
                    Schedule
                </a>
                <a href="{{ route('booking.index') }}" target="_blank" class="text-slate-400 ml-auto">
                    View site &nearr;
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow max-w-6xl w-full mx-auto px-4 sm:px-6 py-6 sm:py-8">
        <!-- Flash notifications -->
        @if (session('success'))
            <div class="mb-5 rounded-md bg-emerald-50 border border-emerald-200 p-3 text-xs text-emerald-800 flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-5 rounded-md bg-rose-50 border border-rose-200 p-3 text-xs text-rose-800 flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-5 rounded-md bg-rose-50 border border-rose-200 p-3 text-xs text-rose-800">
                <div class="font-medium mb-1">Action could not be completed:</div>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-200 bg-white py-4 text-center text-xs text-slate-400">
        <p>&copy; {{ date('Y') }} AppointEase</p>
    </footer>

    @stack('scripts')
</body>
</html>
