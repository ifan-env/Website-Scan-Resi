<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b1020">
    <title>@yield('judul', 'Dashboard') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full">
<div class="min-h-screen lg:flex">

    {{-- Overlay sidebar mobile --}}
    <div id="sidebar-overlay" class="fixed inset-0 z-30 hidden bg-ink-950/40 backdrop-blur-sm lg:hidden"></div>

    {{-- Sidebar --}}
    <aside id="sidebar"
           class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col border-r border-ink-200/80 bg-white transition-transform duration-300 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0">
        <div class="flex items-center gap-3 px-5 pb-5 pt-6">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-lg shadow-brand-600/25">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 8-9-5-9 5 9 5 9-5Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="m3 8 9 5 9-5M12 13v8M6 10.5V16l6 3.3 6-3.3v-5.5"/>
                </svg>
            </span>
            <div class="leading-tight">
                <p class="text-[15px] font-bold tracking-tight text-ink-900">Loker<span class="text-brand-600">Paket</span></p>
                <p class="text-[11px] font-medium uppercase tracking-[0.18em] text-ink-400">Smart Locker AI</p>
            </div>
            <button type="button" id="tutup-sidebar" class="ml-auto rounded-lg p-1.5 text-ink-400 hover:bg-ink-100 lg:hidden" aria-label="Tutup menu">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3.5 py-4">
            <p class="px-3.5 pb-2 text-[10px] font-semibold uppercase tracking-[0.2em] text-ink-400">Menu utama</p>

            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'nav-link-active' : '' }}">
                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/>
                    <rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>
                </svg>
                Dashboard
            </a>

            <a href="{{ route('riwayat') }}" class="nav-link {{ request()->routeIs('riwayat') ? 'nav-link-active' : '' }}">
                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.05 11a9 9 0 1 1 .5 4M3 5v6h6"/>
                </svg>
                Riwayat Prediksi
            </a>

            <a href="{{ route('api.docs') }}" class="nav-link {{ request()->routeIs('api.docs') ? 'nav-link-active' : '' }}">
                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8 9-4 3 4 3M16 9l4 3-4 3M14 5l-4 14"/>
                </svg>
                Dokumentasi API
            </a>

            <p class="px-3.5 pb-2 pt-6 text-[10px] font-semibold uppercase tracking-[0.2em] text-ink-400">Informasi</p>

            <div class="mx-1 rounded-2xl border border-ink-100 bg-ink-50/70 p-4">
                <div class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-brand-500"></span>
                    <p class="text-xs font-semibold text-ink-800">Model SVM RBF</p>
                </div>
                <p class="mt-1.5 text-[11px] leading-relaxed text-ink-500">Klasifikasi resi paket dari kamera atau upload foto.</p>
                <div class="mt-3 grid grid-cols-2 gap-2 text-center">
                    <div class="rounded-xl bg-white px-2 py-2 ring-1 ring-ink-100">
                        <p class="text-sm font-bold text-ink-900">1.812</p>
                        <p class="text-[10px] text-ink-400">fitur</p>
                    </div>
                    <div class="rounded-xl bg-white px-2 py-2 ring-1 ring-ink-100">
                        <p class="text-sm font-bold text-ink-900">C=5</p>
                        <p class="text-[10px] text-ink-400">kernel RBF</p>
                    </div>
                </div>
            </div>
        </nav>

        <div class="border-t border-ink-100 px-5 py-4">
            <p class="text-[11px] leading-relaxed text-ink-400">
                Sistem Cerdas · Semester 5<br>Kelompok 1 — Loker Paket Pintar
            </p>
        </div>
    </aside>

    {{-- Konten --}}
    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-20 border-b border-ink-200/70 bg-ink-50/80 backdrop-blur-xl">
            <div class="flex items-center gap-3 px-4 py-3.5 sm:px-6 lg:px-8">
                <button type="button" id="buka-sidebar" class="rounded-xl border border-ink-200 bg-white p-2 text-ink-600 shadow-sm lg:hidden" aria-label="Buka menu">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>

                <div class="min-w-0">
                    <h1 class="truncate text-base font-bold tracking-tight text-ink-900 sm:text-lg">@yield('judul', 'Dashboard')</h1>
                    <p class="hidden truncate text-xs text-ink-500 sm:block">@yield('subjudul', 'Deteksi resi paket otomatis dengan model SVM')</p>
                </div>

                <div class="ml-auto flex items-center gap-2">
                    @hasSection('aksi-header')
                        @yield('aksi-header')
                    @endif

                    @isset($serviceAktif)
                        @if ($serviceAktif)
                            <span class="chip bg-brand-50 text-brand-700 ring-1 ring-brand-200/70">
                                <span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                                Model online
                            </span>
                        @else
                            <span class="chip bg-amber-50 text-amber-700 ring-1 ring-amber-200" title="Jalankan python ml_service/app.py">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                Model offline
                            </span>
                        @endif
                    @endisset
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-[1400px] flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            @yield('content')

            <footer class="mt-10 flex flex-col items-center justify-between gap-2 border-t border-ink-200/70 pt-5 text-[11px] text-ink-400 sm:flex-row">
                <p>&copy; {{ date('Y') }} Loker Paket Pintar — Sistem Cerdas.</p>
                <p>Ditenagai Laravel 13 &amp; model SVM (scikit-learn)</p>
            </footer>
        </main>
    </div>
</div>

{{-- Toast sukses --}}
@if (session('sukses'))
    <div id="toast" class="fixed bottom-5 left-1/2 z-50 flex -translate-x-1/2 items-center gap-2.5 rounded-xl bg-ink-900 px-4 py-3 text-sm font-medium text-white shadow-xl anim-naik">
        <svg class="h-4 w-4 text-brand-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
        {{ session('sukses') }}
    </div>
@endif

@stack('modals')
</body>
</html>
