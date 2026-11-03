@extends('layouts.app')

@section('judul', 'Dashboard')
@section('subjudul', 'Deteksi resi paket otomatis dengan model SVM')

@php
    $labelKelas = config('prediksi.classes');
@endphp

@section('content')
    {{-- Kartu statistik --}}
    <section class="grid grid-cols-2 gap-3.5 xl:grid-cols-4">
        <div class="card p-4 sm:p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-ink-400">Total prediksi</p>
                    <p id="stat-total" class="mt-2 text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">{{ number_format($stats['total'], 0, ',', '.') }}</p>
                    <p class="mt-1 text-[11px] text-ink-400">semua sumber gambar</p>
                </div>
                <span class="stat-icon bg-ink-100 text-ink-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 19V9m5 10V5m5 14v-7m5 7V8"/></svg>
                </span>
            </div>
        </div>

        <div class="card p-4 sm:p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-ink-400">Ada resi</p>
                    <p id="stat-ada" class="mt-2 text-2xl font-bold tracking-tight text-brand-600 sm:text-3xl">{{ number_format($stats['ada_resi'], 0, ',', '.') }}</p>
                    <p class="mt-1 text-[11px] text-ink-400">label terdeteksi</p>
                </div>
                <span class="stat-icon bg-brand-50 text-brand-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.5 11.5 15 16 9.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 3 4.5 6.5v5c0 4.7 3.2 8.1 7.5 9.5 4.3-1.4 7.5-4.8 7.5-9.5v-5L12 3Z"/></svg>
                </span>
            </div>
        </div>

        <div class="card p-4 sm:p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-ink-400">Tanpa resi</p>
                    <p id="stat-tidak" class="mt-2 text-2xl font-bold tracking-tight text-amber-600 sm:text-3xl">{{ number_format($stats['tidak_ada_resi'], 0, ',', '.') }}</p>
                    <p class="mt-1 text-[11px] text-ink-400">label terdeteksi</p>
                </div>
                <span class="stat-icon bg-amber-50 text-amber-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M8 12h8"/></svg>
                </span>
            </div>
        </div>

        <div class="card p-4 sm:p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-ink-400">Akurasi uji model</p>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">{{ $akurasiPersen !== null ? number_format($akurasiPersen, 1, ',', '.') . '%' : '—' }}</p>
                    <p class="mt-1 text-[11px] text-ink-400">{{ $metrik['jumlah_data'] ? number_format($metrik['jumlah_data'], 0, ',', '.') . ' gambar latih' : 'SVM RBF C=5' }}</p>
                </div>
                <span class="stat-icon bg-indigo-50 text-indigo-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3m0 12v3M3 12h3m12 0h3M5.6 5.6l2.1 2.1m8.6 8.6 2.1 2.1m0-12.8-2.1 2.1m-8.6 8.6-2.1 2.1"/><circle cx="12" cy="12" r="3.2"/></svg>
                </span>
            </div>
        </div>
    </section>

    <div class="mt-5 grid gap-5 xl:grid-cols-5">
        {{-- Panel analisis --}}
        <section class="card overflow-hidden xl:col-span-3">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-ink-100 px-5 py-4 sm:px-6">
                <div>
                    <h2 class="text-sm font-bold text-ink-900">Analisis Paket</h2>
                    <p class="text-xs text-ink-500">Ambil foto langsung atau unggah gambar paket.</p>
                </div>
                <span class="chip bg-ink-100 text-ink-600">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5 12 7.5l-3 3M12 7.5V16"/></svg>
                    JPG · PNG · WEBP
                </span>
            </div>

            <div class="space-y-4 px-5 py-5 sm:px-6">
                <div id="dropzone" class="dropzone" role="button" tabindex="0" aria-label="Unggah gambar paket"
                     data-url-prediksi="{{ route('prediksi.store') }}"
                     data-url-contoh="{{ route('prediksi.contoh') }}">
                    <input id="file-input" type="file" class="hidden" accept="image/jpeg,image/png,image/webp" capture="environment">

                    <div id="dropzone-placeholder" class="flex flex-col items-center">
                        <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-brand-600 shadow-sm ring-1 ring-ink-200/60">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 15v3.5A1.5 1.5 0 0 0 5.5 20h13a1.5 1.5 0 0 0 1.5-1.5V15"/>
                            </svg>
                        </span>
                        <p class="mt-3 text-sm font-semibold text-ink-800">Tarik &amp; lepas foto di sini</p>
                        <p class="mt-1 text-xs text-ink-500">atau klik untuk memilih dari perangkat</p>
                        <p class="mt-3 text-[11px] text-ink-400">Maksimal 10 MB · Foto seluruh badan paket &amp; label</p>
                    </div>

                    <div id="preview-wrap" class="hidden h-full w-full">
                        <img id="preview-gambar" alt="Pratinjau gambar paket" class="h-64 w-full object-contain sm:h-72">
                        <div id="preview-loading" class="absolute inset-0 hidden items-center justify-center bg-ink-950/65 backdrop-blur-[2px]">
                            <div class="flex flex-col items-center gap-3 text-white">
                                <span class="h-9 w-9 animate-spin rounded-full border-[3px] border-white/25 border-t-white"></span>
                                <p class="text-xs font-medium">Menganalisis gambar…</p>
                            </div>
                        </div>
                        <span class="absolute right-3 top-3 chip bg-ink-950/60 text-white backdrop-blur">Pratinjau</span>
                    </div>
                </div>

                <div id="alert-error" class="hidden items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-3 text-xs font-medium text-rose-700">
                    <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 8v4.5M12 16h.01"/></svg>
                    <span id="alert-error-teks"></span>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <label for="file-input" class="btn-primary cursor-pointer">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7.5A1.5 1.5 0 0 1 5.5 6h3l1.5 2h8.5A1.5 1.5 0 0 1 20 9.5v8A1.5 1.5 0 0 1 18.5 19h-13A1.5 1.5 0 0 1 4 17.5v-10Z"/></svg>
                        Pilih Gambar
                    </label>
                    <button type="button" id="tombol-kamera" class="btn-brand">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8.5A1.5 1.5 0 0 1 5.5 7h2l1.2-2h6.6L16.5 7h2A1.5 1.5 0 0 1 20 8.5v9a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 17.5v-9Z"/><circle cx="12" cy="13" r="3.2"/></svg>
                        Ambil dari Kamera
                    </button>
                    <button type="button" id="tombol-reset" class="btn-ghost hidden">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5"/><path stroke-linecap="round" stroke-linejoin="round" d="M5.6 15a7.5 7.5 0 0 0 12.2 2.6L20 15M4 9l2.2-2.6A7.5 7.5 0 0 1 18.4 9"/></svg>
                        Reset
                    </button>
                </div>

                @if (count($contoh))
                    <div class="border-t border-ink-100 pt-4">
                        <div class="mb-2.5 flex items-center justify-between">
                            <p class="text-xs font-semibold text-ink-700">Coba contoh dari dataset</p>
                            <span class="text-[10px] font-medium uppercase tracking-wider text-ink-400">klik untuk analisis</span>
                        </div>
                        <div class="scroll-tipis flex gap-2.5 overflow-x-auto pb-1.5">
                            @foreach ($contoh as $i => $item)
                                <button type="button"
                                        class="contoh-item group relative h-16 w-16 shrink-0 overflow-hidden rounded-xl border border-ink-200 bg-ink-100 transition hover:-translate-y-0.5 hover:border-brand-400 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                                        data-url="{{ $item['url'] }}"
                                        data-label="{{ $item['label'] }}"
                                        data-nama="{{ $item['nama'] }}"
                                        title="Analisis contoh {{ $i + 1 }}">
                                    <img src="{{ $item['url'] }}" alt="Contoh gambar {{ $i + 1 }}" loading="lazy" class="h-full w-full object-cover transition group-hover:scale-105">
                                    <span class="absolute inset-x-0 bottom-0 bg-ink-950/55 py-0.5 text-[9px] font-semibold text-white">{{ $i + 1 }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </section>

        {{-- Panel hasil --}}
        <section class="card flex flex-col xl:col-span-2">
            <div class="flex items-center justify-between gap-3 border-b border-ink-100 px-5 py-4 sm:px-6">
                <div>
                    <h2 class="text-sm font-bold text-ink-900">Hasil Analisis</h2>
                    <p class="text-xs text-ink-500">Prediksi model untuk gambar terpilih.</p>
                </div>
                <span class="chip bg-ink-100 text-ink-600">SVM</span>
            </div>

            <div class="flex-1 px-5 py-5 sm:px-6">
                {{-- Kosong --}}
                <div id="hasil-idle" class="flex h-full min-h-72 flex-col items-center justify-center text-center">
                    <span class="flex h-16 w-16 items-center justify-center rounded-2xl border border-dashed border-ink-300 bg-ink-50 text-ink-300">
                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.5 12s3-6.5 8.5-6.5S20.5 12 20.5 12s-3 6.5-8.5 6.5S3.5 12 3.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                    </span>
                    <p class="mt-4 text-sm font-semibold text-ink-700">Belum ada gambar</p>
                    <p class="mt-1 max-w-[240px] text-xs leading-relaxed text-ink-400">Unggah foto paket, ambil dari kamera, atau pilih contoh untuk melihat hasil prediksi.</p>
                </div>

                {{-- Memuat --}}
                <div id="hasil-loading" class="hidden h-full min-h-72 flex-col items-center justify-center text-center">
                    <span class="relative flex h-16 w-16 items-center justify-center">
                        <span class="absolute inset-0 animate-ping rounded-full bg-brand-200/60"></span>
                        <span class="relative flex h-12 w-12 items-center justify-center rounded-full bg-brand-600 text-white shadow-lg shadow-brand-600/30">
                            <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" d="M12 3a9 9 0 1 0 9 9"/></svg>
                        </span>
                    </span>
                    <p class="mt-4 text-sm font-semibold text-ink-700">Model sedang menganalisis</p>
                    <p class="mt-1 text-xs text-ink-400">Ekstraksi HOG + histogram warna…</p>
                </div>

                {{-- Hasil --}}
                <div id="hasil-konten" class="hidden">
                    <div id="hasil-verdict" class="flex items-center gap-4 rounded-2xl border p-4">
                        <span id="hasil-ikon" class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-ink-400">Prediksi model</p>
                            <p id="hasil-nama" class="truncate text-xl font-bold tracking-tight">—</p>
                        </div>
                        <div class="ml-auto shrink-0 text-right">
                            <p id="hasil-confidence" class="text-xl font-bold">—</p>
                            <p class="text-[10px] font-medium text-ink-400">keyakinan</p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-3.5">
                        <div>
                            <div class="mb-1.5 flex items-center justify-between text-xs">
                                <span class="font-medium text-ink-600">Ada Resi</span>
                                <span id="prob-ada-teks" class="font-semibold text-ink-800">0%</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-ink-100">
                                <div id="prob-ada-bar" class="h-full rounded-full bg-brand-500 transition-all duration-700" style="width: 0%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="mb-1.5 flex items-center justify-between text-xs">
                                <span class="font-medium text-ink-600">Tidak Ada Resi</span>
                                <span id="prob-tidak-teks" class="font-semibold text-ink-800">0%</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-ink-100">
                                <div id="prob-tidak-bar" class="h-full rounded-full bg-amber-500 transition-all duration-700" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>

                    <dl class="mt-5 grid grid-cols-2 gap-2.5">
                        <div class="rounded-xl border border-ink-100 bg-ink-50/60 px-3 py-2.5">
                            <dt class="text-[10px] font-medium uppercase tracking-wider text-ink-400">Waktu proses</dt>
                            <dd id="meta-waktu" class="mt-0.5 text-sm font-semibold text-ink-800">—</dd>
                        </div>
                        <div class="rounded-xl border border-ink-100 bg-ink-50/60 px-3 py-2.5">
                            <dt class="text-[10px] font-medium uppercase tracking-wider text-ink-400">Jumlah fitur</dt>
                            <dd id="meta-fitur" class="mt-0.5 text-sm font-semibold text-ink-800">—</dd>
                        </div>
                        <div class="rounded-xl border border-ink-100 bg-ink-50/60 px-3 py-2.5">
                            <dt class="text-[10px] font-medium uppercase tracking-wider text-ink-400">Sumber</dt>
                            <dd id="meta-sumber" class="mt-0.5 text-sm font-semibold capitalize text-ink-800">—</dd>
                        </div>
                        <div class="rounded-xl border border-ink-100 bg-ink-50/60 px-3 py-2.5">
                            <dt class="text-[10px] font-medium uppercase tracking-wider text-ink-400">Model</dt>
                            <dd class="mt-0.5 text-sm font-semibold text-ink-800">SVM RBF</dd>
                        </div>
                    </dl>

                    <div id="hasil-dataset" class="mt-3 hidden items-start gap-2 rounded-xl border px-3 py-2.5 text-[11px] font-medium">
                        <svg class="mt-0.5 h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 8h.01M11 12h1v4h1"/></svg>
                        <span id="hasil-dataset-teks"></span>
                    </div>

                    <div class="mt-4 flex gap-2">
                        <button type="button" id="analisis-lagi" class="btn-ghost flex-1">Analisis Lagi</button>
                        <a href="{{ route('riwayat') }}" class="btn-primary flex-1">Lihat Riwayat</a>
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- Prediksi terbaru --}}
    <section class="card mt-5">
        <div class="flex items-center justify-between gap-3 border-b border-ink-100 px-5 py-4 sm:px-6">
            <div>
                <h2 class="text-sm font-bold text-ink-900">Prediksi Terbaru</h2>
                <p class="text-xs text-ink-500">Aktivitas analisis terakhir.</p>
            </div>
            <a href="{{ route('riwayat') }}" class="text-xs font-semibold text-brand-700 hover:text-brand-800">Lihat semua →</a>
        </div>

        <div id="daftar-terbaru" class="divide-y divide-ink-100">
            @forelse ($terbaru as $item)
                <div class="flex items-center gap-3.5 px-5 py-3.5 sm:px-6">
                    <img src="{{ $item->gambar_url }}" alt="" class="h-11 w-11 shrink-0 rounded-xl border border-ink-100 object-cover">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-ink-800">{{ $item->nama_kelas }}</p>
                        <p class="mt-0.5 truncate text-[11px] text-ink-400">
                            {{ $item->created_at->diffForHumans() }} · {{ $item->sumber }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold {{ $item->ada_resi ? 'text-brand-600' : 'text-amber-600' }}">{{ $item->confidence_persen }}%</p>
                        <p class="text-[10px] text-ink-400">keyakinan</p>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center px-5 py-10 text-center">
                    <p class="text-sm font-medium text-ink-500">Belum ada prediksi.</p>
                    <p class="mt-1 text-xs text-ink-400">Hasil analisis pertama akan tampil di sini.</p>
                </div>
            @endforelse
        </div>
    </section>
@endsection

@push('modals')
    @include('partials.camera-modal')
@endpush
