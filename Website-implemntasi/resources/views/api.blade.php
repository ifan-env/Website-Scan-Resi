@extends('layouts.app')

@section('judul', 'Dokumentasi API')
@section('subjudul', 'Integrasikan prediksi resi ke aplikasi lain')

@section('content')
    <div class="grid gap-5 xl:grid-cols-3">
        <div class="space-y-5 xl:col-span-2">
            <section class="card overflow-hidden">
                <div class="border-b border-ink-100 px-5 py-4 sm:px-6">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="chip bg-brand-600 text-white">POST</span>
                        <code class="text-sm font-semibold text-ink-900">/api/predict</code>
                    </div>
                    <p class="mt-1.5 text-xs leading-relaxed text-ink-500">
                        Kirim gambar paket untuk diklasifikasikan menjadi <strong>ada_resi</strong> atau
                        <strong>tidak_ada_resi</strong>. Mendukung multipart upload dan JSON base64.
                    </p>
                </div>
                <div class="space-y-5 px-5 py-5 sm:px-6">
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-ink-400">Parameter</h3>
                        <div class="mt-2 overflow-hidden rounded-xl border border-ink-100">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-ink-50 text-[10px] uppercase tracking-wider text-ink-500">
                                    <tr>
                                        <th class="px-3.5 py-2.5">Field</th>
                                        <th class="px-3.5 py-2.5">Tipe</th>
                                        <th class="px-3.5 py-2.5">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-ink-100 bg-white">
                                    <tr>
                                        <td class="px-3.5 py-2.5 font-mono font-semibold text-ink-800">file</td>
                                        <td class="px-3.5 py-2.5 text-ink-500">file</td>
                                        <td class="px-3.5 py-2.5 text-ink-500">Gambar JPG/PNG/WEBP, maks. 10 MB.</td>
                                    </tr>
                                    <tr>
                                        <td class="px-3.5 py-2.5 font-mono font-semibold text-ink-800">gambar_base64</td>
                                        <td class="px-3.5 py-2.5 text-ink-500">string</td>
                                        <td class="px-3.5 py-2.5 text-ink-500">Alternatif file, data base64 (boleh dengan prefix data URI).</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-ink-400">Contoh cURL</h3>
                        <pre class="scroll-tipis mt-2 overflow-x-auto rounded-xl bg-ink-950 px-4 py-3.5 text-[11px] leading-relaxed text-ink-100"><code>curl -X POST \
  -F "file=@paket.jpg" \
  {{ url('/api/predict') }}</code></pre>
                    </div>

                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-ink-400">Contoh JavaScript</h3>
                        <pre class="scroll-tipis mt-2 overflow-x-auto rounded-xl bg-ink-950 px-4 py-3.5 text-[11px] leading-relaxed text-ink-100"><code>@verbatim
const fd = new FormData();
fd.append('file', fileInput.files[0]);

const res = await fetch('/api/predict', {
    method: 'POST',
    headers: { Accept: 'application/json' },
    body: fd,
});

const { data } = await res.json();
console.log(data.prediksi, data.kepercayaan);
@endverbatim</code></pre>
                    </div>

                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-ink-400">Contoh respons <span class="normal-case tracking-normal">(201)</span></h3>
                        <pre class="scroll-tipis mt-2 overflow-x-auto rounded-xl bg-ink-950 px-4 py-3.5 text-[11px] leading-relaxed text-ink-100"><code>{
  "message": "Analisis berhasil.",
  "data": {
    "id": 12,
    "prediksi": "ada_resi",
    "nama_kelas": "Ada Resi",
    "kepercayaan": 0.9682,
    "probabilitas": {
      "ada_resi": 0.9682,
      "tidak_ada_resi": 0.0318
    },
    "waktu_proses_detik": 0.214,
    "jumlah_fitur": 1812,
    "sumber": "api",
    "gambar_url": "{{ url('/storage/prediksi/contoh.jpg') }}",
    "dibuat_pada": "2026-11-02T10:30:00+07:00"
  }
}</code></pre>
                    </div>
                </div>
            </section>

            <section class="card overflow-hidden">
                <div class="border-b border-ink-100 px-5 py-4 sm:px-6">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="chip bg-ink-100 text-ink-700">GET</span>
                        <code class="text-sm font-semibold text-ink-900">/api/model</code>
                    </div>
                    <p class="mt-1.5 text-xs text-ink-500">Informasi model, daftar kelas, metrik evaluasi, dan status service Python.</p>
                </div>
                <div class="px-5 py-5 sm:px-6">
                    <pre class="scroll-tipis overflow-x-auto rounded-xl bg-ink-950 px-4 py-3.5 text-[11px] leading-relaxed text-ink-100"><code>curl {{ url('/api/model') }}</code></pre>
                </div>
            </section>
        </div>

        <aside class="space-y-5">
            <section class="card p-5">
                <h2 class="text-sm font-bold text-ink-900">Base URL</h2>
                <div class="mt-2 flex items-center justify-between gap-2 rounded-xl border border-ink-100 bg-ink-50 px-3 py-2.5">
                    <code class="truncate text-xs font-semibold text-ink-700">{{ url('/api') }}</code>
                </div>
                <p class="mt-3 text-[11px] leading-relaxed text-ink-400">
                    Kirim header <code class="rounded bg-ink-100 px-1 py-0.5 text-ink-600">Accept: application/json</code>
                    agar error validasi dikembalikan dalam format JSON.
                </p>
            </section>

            <section class="card p-5">
                <h2 class="text-sm font-bold text-ink-900">Endpoint lain</h2>
                <ul class="mt-3 space-y-3 text-xs">
                    <li>
                        <div class="flex items-center gap-2">
                            <span class="chip bg-ink-100 text-ink-700">GET</span>
                            <code class="font-semibold text-ink-800">/api/health</code>
                        </div>
                        <p class="mt-1 text-[11px] text-ink-500">Status aplikasi dan service model.</p>
                    </li>
                    <li>
                        <div class="flex items-center gap-2">
                            <span class="chip bg-ink-100 text-ink-700">GET</span>
                            <code class="font-semibold text-ink-800">/api/model</code>
                        </div>
                        <p class="mt-1 text-[11px] text-ink-500">Detail model &amp; metrik training.</p>
                    </li>
                    <li>
                        <div class="flex items-center gap-2">
                            <span class="chip bg-ink-100 text-ink-700">GET</span>
                            <code class="font-semibold text-ink-800">/up</code>
                        </div>
                        <p class="mt-1 text-[11px] text-ink-500">Health check bawaan Laravel.</p>
                    </li>
                </ul>
            </section>

            <section class="rounded-2xl bg-gradient-to-br from-ink-900 to-ink-950 p-5 text-white shadow-soft">
                <h2 class="text-sm font-bold">Arsitektur</h2>
                <ol class="mt-3 space-y-2.5 text-[11px] leading-relaxed text-ink-300">
                    <li class="flex gap-2"><span class="font-bold text-brand-400">1.</span> Laravel menerima gambar &amp; validasi.</li>
                    <li class="flex gap-2"><span class="font-bold text-brand-400">2.</span> Laravel memanggil service Python (Flask).</li>
                    <li class="flex gap-2"><span class="font-bold text-brand-400">3.</span> Preprocessing CLAHE + Bilateral + HOG.</li>
                    <li class="flex gap-2"><span class="font-bold text-brand-400">4.</span> SVM RBF memprediksi kelas &amp; probabilitas.</li>
                    <li class="flex gap-2"><span class="font-bold text-brand-400">5.</span> Hasil disimpan ke MySQL &amp; ditampilkan.</li>
                </ol>
            </section>
        </aside>
    </div>
@endsection
