{{-- Modal kamera langsung (getUserMedia) --}}
<div id="modal-kamera" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-ink-950/70 backdrop-blur-sm" data-tutup-kamera></div>

    <div class="relative z-10 w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl anim-naik" role="dialog" aria-modal="true" aria-labelledby="judul-kamera">
        <div class="flex items-center justify-between gap-3 border-b border-ink-100 px-5 py-4">
            <div class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8.5A1.5 1.5 0 0 1 5.5 7h2l1.2-2h6.6L16.5 7h2A1.5 1.5 0 0 1 20 8.5v9a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 17.5v-9Z"/><circle cx="12" cy="13" r="3.2"/></svg>
                </span>
                <div>
                    <h2 id="judul-kamera" class="text-sm font-bold text-ink-900">Ambil Foto Paket</h2>
                    <p class="text-[11px] text-ink-500">Posisikan label resi di dalam bingkai.</p>
                </div>
            </div>
            <button type="button" class="rounded-lg p-1.5 text-ink-400 transition hover:bg-ink-100 hover:text-ink-700" data-tutup-kamera aria-label="Tutup kamera">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>

        <div class="space-y-3 px-5 py-4">
            <div class="relative aspect-video overflow-hidden rounded-xl bg-ink-950">
                <video id="kamera-video" autoplay playsinline muted class="h-full w-full object-cover"></video>
                <img id="kamera-jepretan" alt="Hasil jepretan kamera" class="hidden h-full w-full object-cover">

                <div class="pointer-events-none absolute inset-6 rounded-lg border-2 border-white/35"></div>

                <div id="kamera-status" class="absolute inset-0 flex flex-col items-center justify-center gap-2.5 bg-ink-950/80 text-white">
                    <span class="h-8 w-8 animate-spin rounded-full border-[3px] border-white/25 border-t-white"></span>
                    <p class="text-xs font-medium">Menyalakan kamera…</p>
                </div>

                <div id="kamera-error" class="absolute inset-0 hidden flex-col items-center justify-center gap-2 bg-ink-950/90 px-6 text-center text-white">
                    <svg class="h-8 w-8 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4.5M12 17h.01"/><path stroke-linecap="round" stroke-linejoin="round" d="M10.3 4.2 2.6 17.4A1.6 1.6 0 0 0 4 20h16a1.6 1.6 0 0 0 1.4-2.6L13.7 4.2a2 2 0 0 0-3.4 0Z"/></svg>
                    <p id="kamera-error-teks" class="text-xs leading-relaxed"></p>
                </div>
            </div>

            <p class="text-center text-[11px] text-ink-400">
                Kamera memerlukan izin browser dan koneksi <span class="font-semibold text-ink-600">localhost</span> atau HTTPS.
            </p>
        </div>

        <div class="flex items-center justify-between gap-2 border-t border-ink-100 bg-ink-50/60 px-5 py-3.5">
            <button type="button" id="kamera-ganti" class="btn-ghost !px-3" title="Ganti kamera depan/belakang" disabled>
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M4 9a8 8 0 0 1 13.7-4.3L20 7M20 15a8 8 0 0 1-13.7 4.3L4 17"/><path stroke-linecap="round" d="M20 3v4h-4M4 21v-4h4"/></svg>
                Ganti
            </button>

            <div class="flex items-center gap-2">
                <button type="button" id="kamera-ulangi" class="btn-ghost hidden">Ambil Ulang</button>
                <button type="button" id="kamera-gunakan" class="btn-brand hidden">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                    Gunakan Foto
                </button>
                <button type="button" id="kamera-jepret" class="btn-brand" disabled>
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3"/></svg>
                    Jepret
                </button>
            </div>
        </div>
    </div>
</div>
