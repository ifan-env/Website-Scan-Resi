@extends('layouts.app')

@section('judul', 'Riwayat Prediksi')
@section('subjudul', 'Semua hasil analisis paket yang tersimpan di database')

@section('content')
    <section class="grid grid-cols-3 gap-3.5">
        <div class="card p-4 sm:p-5">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-ink-400">Total data</p>
            <p class="mt-1.5 text-2xl font-bold text-ink-900">{{ number_format($stats['total'], 0, ',', '.') }}</p>
        </div>
        <div class="card p-4 sm:p-5">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-ink-400">Ada resi</p>
            <p class="mt-1.5 text-2xl font-bold text-brand-600">{{ number_format($stats['ada_resi'], 0, ',', '.') }}</p>
        </div>
        <div class="card p-4 sm:p-5">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-ink-400">Rata-rata keyakinan</p>
            <p class="mt-1.5 text-2xl font-bold text-ink-900">{{ number_format($stats['akurasi_rata'], 1, ',', '.') }}%</p>
        </div>
    </section>

    <section class="card mt-5 overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-ink-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h2 class="text-sm font-bold text-ink-900">Daftar Prediksi</h2>
                <p class="text-xs text-ink-500">Data tersimpan otomatis setelah analisis berhasil.</p>
            </div>

            <form method="GET" action="{{ route('riwayat') }}" class="flex flex-wrap items-end gap-2">
                <div>
                    <label for="filter-label" class="label-form">Label</label>
                    <select id="filter-label" name="label" class="input-form !w-40 !py-2">
                        <option value="">Semua label</option>
                        @foreach (config('prediksi.classes') as $nilai => $nama)
                            <option value="{{ $nilai }}" @selected($filter['label'] === $nilai)>{{ $nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filter-sumber" class="label-form">Sumber</label>
                    <select id="filter-sumber" name="sumber" class="input-form !w-36 !py-2">
                        <option value="">Semua sumber</option>
                        @foreach (['upload' => 'Upload', 'kamera' => 'Kamera', 'contoh' => 'Contoh', 'api' => 'API'] as $nilai => $nama)
                            <option value="{{ $nilai }}" @selected($filter['sumber'] === $nilai)>{{ $nama }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn-primary !py-2">Filter</button>
                @if ($filter['label'] || $filter['sumber'])
                    <a href="{{ route('riwayat') }}" class="btn-ghost !py-2">Reset</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="table-head">
                    <tr>
                        <th class="px-5 py-3 sm:px-6">Gambar</th>
                        <th class="px-4 py-3">Prediksi</th>
                        <th class="px-4 py-3">Keyakinan</th>
                        <th class="px-4 py-3">Probabilitas</th>
                        <th class="px-4 py-3">Sumber</th>
                        <th class="px-4 py-3">Waktu</th>
                        <th class="px-4 py-3 text-right sm:px-6">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($riwayat as $item)
                        <tr class="transition hover:bg-ink-50/60">
                            <td class="px-5 py-3 sm:px-6">
                                <div class="flex items-center gap-3">
                                    <a href="{{ $item->gambar_url }}" target="_blank" class="block shrink-0">
                                        <img src="{{ $item->gambar_url }}" alt="{{ $item->nama_asli }}" class="h-12 w-12 rounded-xl border border-ink-100 object-cover">
                                    </a>
                                    <div class="min-w-0">
                                        <p class="max-w-[180px] truncate text-xs font-semibold text-ink-700">{{ $item->nama_asli ?: 'Tanpa nama' }}</p>
                                        <p class="mt-0.5 text-[10px] text-ink-400">{{ number_format($item->waktu_proses_ms, 0, ',', '.') }} ms · {{ $item->jumlah_fitur }} fitur</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="chip {{ $item->ada_resi ? 'bg-brand-50 text-brand-700' : 'bg-amber-50 text-amber-700' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $item->ada_resi ? 'bg-brand-500' : 'bg-amber-500' }}"></span>
                                    {{ $item->nama_kelas }}
                                </span>
                                @if ($item->label_dataset)
                                    <p class="mt-1 text-[10px] {{ $item->label_dataset === $item->label ? 'text-brand-600' : 'text-rose-500' }}">
                                        Label asli: {{ config('prediksi.classes')[$item->label_dataset] ?? $item->label_dataset }}
                                    </p>
                                @endif
                                <p class="mt-1 break-all text-[10px] text-ink-500">
                                    Nomor: <span class="font-semibold text-ink-700">{{ $item->nomor_resi ?? 'tidak terbaca' }}</span>
                                    @if ($item->metode_baca)
                                        ({{ $item->metode_baca }})
                                    @endif
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="w-11 text-xs font-bold text-ink-800">{{ $item->confidence_persen }}%</span>
                                    <div class="h-1.5 w-16 overflow-hidden rounded-full bg-ink-100">
                                        <div class="h-full rounded-full {{ $item->ada_resi ? 'bg-brand-500' : 'bg-amber-500' }}" style="width: {{ min(100, $item->confidence_persen) }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-[11px] text-ink-500">Resi: <span class="font-semibold text-ink-700">{{ round($item->prob_ada_resi * 100, 1) }}%</span></p>
                                <p class="text-[11px] text-ink-500">Bukan: <span class="font-semibold text-ink-700">{{ round($item->prob_bukan_resi * 100, 1) }}%</span></p>
                                <p class="text-[11px] text-ink-500">Tanpa: <span class="font-semibold text-ink-700">{{ round($item->prob_tidak_ada_resi * 100, 1) }}%</span></p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="chip bg-ink-100 capitalize text-ink-600">{{ $item->sumber }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-xs font-medium text-ink-700">{{ $item->created_at->format('d M Y') }}</p>
                                <p class="text-[10px] text-ink-400">{{ $item->created_at->format('H:i') }} WIB</p>
                            </td>
                            <td class="px-4 py-3 text-right sm:px-6">
                                <form method="POST" action="{{ route('riwayat.destroy', $item) }}" data-konfirmasi="Hapus data prediksi ini? Gambar juga akan dihapus.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg p-2 text-ink-400 transition hover:bg-rose-50 hover:text-rose-600" title="Hapus">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2m-8 0 1 12.5a1.5 1.5 0 0 0 1.5 1.5h5a1.5 1.5 0 0 0 1.5-1.5L18 7"/></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-14 text-center">
                                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-ink-50 text-ink-300">
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2"/><path stroke-linecap="round" stroke-linejoin="round" d="M3.05 11a9 9 0 1 1 .5 4M3 5v6h6"/></svg>
                                </span>
                                <p class="mt-3 text-sm font-semibold text-ink-700">Belum ada riwayat</p>
                                <p class="mt-1 text-xs text-ink-400">Lakukan analisis paket dari dashboard terlebih dahulu.</p>
                                <a href="{{ route('dashboard') }}" class="btn-brand mt-4">Ke Dashboard</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($riwayat->hasPages())
            <div class="border-t border-ink-100 px-5 py-4 sm:px-6">
                {{ $riwayat->links() }}
            </div>
        @endif
    </section>
@endsection
