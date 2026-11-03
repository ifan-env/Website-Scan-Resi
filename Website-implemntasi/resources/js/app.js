const pilih = (selector, root = document) => root.querySelector(selector);

document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initToast();
    initKonfirmasi();
    initDashboard();
});

/* ------------------------------------------------------------------ */
/* Sidebar & toast                                                     */
/* ------------------------------------------------------------------ */
function initSidebar() {
    const sidebar = pilih('#sidebar');
    const overlay = pilih('#sidebar-overlay');
    if (!sidebar || !overlay) return;

    const buka = () => {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
        document.body.classList.add('overflow-hidden', 'lg:overflow-auto');
    };

    const tutup = () => {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
        document.body.classList.remove('overflow-hidden', 'lg:overflow-auto');
    };

    pilih('#buka-sidebar')?.addEventListener('click', buka);
    pilih('#tutup-sidebar')?.addEventListener('click', tutup);
    overlay.addEventListener('click', tutup);
}

function initToast() {
    const toast = pilih('#toast');
    if (!toast) return;

    setTimeout(() => {
        toast.style.transition = 'opacity .35s ease, transform .35s ease';
        toast.style.opacity = '0';
        toast.style.transform = 'translate(-50%, 10px)';
        setTimeout(() => toast.remove(), 400);
    }, 4000);
}

function initKonfirmasi() {
    document.querySelectorAll('form[data-konfirmasi]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.konfirmasi)) {
                event.preventDefault();
            }
        });
    });
}

/* ------------------------------------------------------------------ */
/* Dashboard: upload, contoh, kamera, hasil                            */
/* ------------------------------------------------------------------ */
function initDashboard() {
    const dropzone = pilih('#dropzone');
    if (!dropzone) return;

    const csrf = pilih('meta[name="csrf-token"]')?.content ?? '';
    const urlPrediksi = dropzone.dataset.urlPrediksi ?? '/prediksi';
    const urlContoh = dropzone.dataset.urlContoh ?? '/prediksi/contoh';

    const inputFile = pilih('#file-input');
    const placeholder = pilih('#dropzone-placeholder');
    const previewWrap = pilih('#preview-wrap');
    const previewGambar = pilih('#preview-gambar');
    const previewLoading = pilih('#preview-loading');
    const tombolKamera = pilih('#tombol-kamera');
    const tombolReset = pilih('#tombol-reset');
    const analisisLagi = pilih('#analisis-lagi');

    const alertError = pilih('#alert-error');
    const alertErrorTeks = pilih('#alert-error-teks');

    const hasilIdle = pilih('#hasil-idle');
    const hasilLoading = pilih('#hasil-loading');
    const hasilKonten = pilih('#hasil-konten');
    const hasilVerdict = pilih('#hasil-verdict');
    const hasilIkon = pilih('#hasil-ikon');
    const hasilNama = pilih('#hasil-nama');
    const hasilConfidence = pilih('#hasil-confidence');

    const probAdaBar = pilih('#prob-ada-bar');
    const probAdaTeks = pilih('#prob-ada-teks');
    const probTidakBar = pilih('#prob-tidak-bar');
    const probTidakTeks = pilih('#prob-tidak-teks');

    const hasilDataset = pilih('#hasil-dataset');
    const hasilDatasetTeks = pilih('#hasil-dataset-teks');

    let objekUrl = null;
    let sedangMemproses = false;

    /* ----------------------- util tampilan ----------------------- */
    const sembunyikanError = () => {
        alertError?.classList.add('hidden');
        alertError?.classList.remove('flex');
    };

    const tampilkanError = (pesan) => {
        if (!alertError) return;
        alertErrorTeks.textContent = pesan;
        alertError.classList.remove('hidden');
        alertError.classList.add('flex');
        alertError.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    };

    const aturLoading = (aktif) => {
        sedangMemproses = aktif;

        if (previewLoading) {
            previewLoading.classList.toggle('hidden', !aktif);
            previewLoading.classList.toggle('flex', aktif);
        }

        tombolKamera?.toggleAttribute('disabled', aktif);
        tombolReset?.toggleAttribute('disabled', aktif);
        inputFile?.toggleAttribute('disabled', aktif);

        if (aktif) {
            hasilIdle?.classList.add('hidden');
            hasilKonten?.classList.add('hidden');
            hasilLoading?.classList.remove('hidden');
            hasilLoading?.classList.add('flex');
        } else {
            hasilLoading?.classList.add('hidden');
            hasilLoading?.classList.remove('flex');
        }
    };

    const tampilkanPratinjau = (file) => {
        if (!file) return;

        if (objekUrl) URL.revokeObjectURL(objekUrl);
        objekUrl = URL.createObjectURL(file);
        previewGambar.src = objekUrl;
        previewGambar.classList.remove('hidden');

        placeholder?.classList.add('hidden');
        previewWrap?.classList.remove('hidden');
        dropzone.classList.add('punya-gambar');
        tombolReset?.classList.remove('hidden');
        sembunyikanError();
    };

    const resetSemua = () => {
        if (objekUrl) URL.revokeObjectURL(objekUrl);
        objekUrl = null;
        if (previewGambar) previewGambar.removeAttribute('src');
        if (inputFile) inputFile.value = '';

        placeholder?.classList.remove('hidden');
        previewWrap?.classList.add('hidden');
        dropzone.classList.remove('punya-gambar');
        tombolReset?.classList.add('hidden');

        hasilKonten?.classList.add('hidden');
        hasilLoading?.classList.add('hidden');
        hasilLoading?.classList.remove('flex');
        hasilIdle?.classList.remove('hidden');

        sembunyikanError();
    };

    /* ------------------------- render hasil ----------------------- */
    const renderHasil = (hasil) => {
        const ada = hasil.prediksi === 'ada_resi';
        const persen = ((hasil.kepercayaan ?? 0) * 100).toFixed(1).replace('.', ',');
        const probAda = ((hasil.probabilitas?.ada_resi ?? 0) * 100).toFixed(1).replace('.', ',');
        const probTidak = ((hasil.probabilitas?.tidak_ada_resi ?? 0) * 100).toFixed(1).replace('.', ',');

        hasilIdle?.classList.add('hidden');
        hasilLoading?.classList.add('hidden');
        hasilLoading?.classList.remove('flex');
        hasilKonten?.classList.remove('hidden');
        hasilKonten?.classList.add('anim-naik');

        if (hasilVerdict) {
            hasilVerdict.className =
                'flex items-center gap-4 rounded-2xl border p-4 ' +
                (ada ? 'border-brand-200 bg-brand-50/70' : 'border-amber-200 bg-amber-50/70');
        }

        if (hasilIkon) {
            hasilIkon.className =
                'flex h-12 w-12 shrink-0 items-center justify-center rounded-xl ' +
                (ada ? 'bg-brand-600 text-white' : 'bg-amber-500 text-white');
        }

        if (hasilNama) {
            hasilNama.textContent = hasil.nama_kelas ?? hasil.prediksi;
            hasilNama.className =
                'truncate text-xl font-bold tracking-tight ' +
                (ada ? 'text-brand-700' : 'text-amber-700');
        }

        if (hasilConfidence) {
            hasilConfidence.textContent = persen + '%';
            hasilConfidence.className =
                'text-xl font-bold ' + (ada ? 'text-brand-700' : 'text-amber-700');
        }

        if (probAdaBar) probAdaBar.style.width = probAda + '%';
        if (probTidakBar) probTidakBar.style.width = probTidak + '%';
        if (probAdaTeks) probAdaTeks.textContent = probAda + '%';
        if (probTidakTeks) probTidakTeks.textContent = probTidak + '%';

        pilih('#meta-waktu').textContent =
            (hasil.waktu_proses_detik ?? 0).toFixed(3).replace('.', ',') + ' detik';
        pilih('#meta-fitur').textContent = Number(hasil.jumlah_fitur ?? 1812).toLocaleString('id-ID');
        pilih('#meta-sumber').textContent = hasil.sumber ?? 'upload';

        if (hasilDataset && hasilDatasetTeks) {
            if (hasil.label_dataset) {
                const benar = hasil.label_dataset === hasil.prediksi;
                const namaAsli = hasil.label_dataset === 'ada_resi' ? 'Ada Resi' : 'Tidak Ada Resi';

                hasilDataset.className =
                    'mt-3 flex items-start gap-2 rounded-xl border px-3 py-2.5 text-[11px] font-medium ' +
                    (benar
                        ? 'border-brand-200 bg-brand-50 text-brand-700'
                        : 'border-rose-200 bg-rose-50 text-rose-700');

                hasilDatasetTeks.textContent =
                    `Label asli dataset: ${namaAsli} — ${benar ? 'prediksi sesuai.' : 'prediksi berbeda.'}`;
            } else {
                hasilDataset.className = 'mt-3 hidden';
                hasilDatasetTeks.textContent = '';
            }
        }
    };

    const perbaruiStatistik = (hasil) => {
        const tambah = (id) => {
            const el = pilih('#' + id);
            if (!el) return;
            const angka = parseInt(el.textContent.replace(/\D/g, ''), 10) || 0;
            el.textContent = (angka + 1).toLocaleString('id-ID');
        };

        tambah('stat-total');
        tambah(hasil.prediksi === 'ada_resi' ? 'stat-ada' : 'stat-tidak');
    };

    const tambahTerbaru = (hasil) => {
        const wadah = pilih('#daftar-terbaru');
        if (!wadah) return;

        const kosong = wadah.querySelector('.py-10');
        if (kosong) kosong.parentElement?.remove();

        const baris = document.createElement('div');
        baris.className = 'flex items-center gap-3.5 px-5 py-3.5 sm:px-6 anim-masuk';

        const gambar = document.createElement('img');
        gambar.src = hasil.gambar_url ?? '';
        gambar.alt = '';
        gambar.className = 'h-11 w-11 shrink-0 rounded-xl border border-ink-100 object-cover';

        const teks = document.createElement('div');
        teks.className = 'min-w-0 flex-1';

        const judul = document.createElement('p');
        judul.className = 'truncate text-sm font-semibold text-ink-800';
        judul.textContent = hasil.nama_kelas ?? hasil.prediksi;

        const meta = document.createElement('p');
        meta.className = 'mt-0.5 truncate text-[11px] text-ink-400';
        meta.textContent = `baru saja · ${hasil.sumber ?? 'upload'}`;

        teks.append(judul, meta);

        const kanan = document.createElement('div');
        kanan.className = 'text-right';

        const nilai = document.createElement('p');
        const ada = hasil.prediksi === 'ada_resi';
        nilai.className = 'text-sm font-bold ' + (ada ? 'text-brand-600' : 'text-amber-600');
        nilai.textContent = ((hasil.kepercayaan ?? 0) * 100).toFixed(1).replace('.', ',') + '%';

        const label = document.createElement('p');
        label.className = 'text-[10px] text-ink-400';
        label.textContent = 'keyakinan';

        kanan.append(nilai, label);
        baris.append(gambar, teks, kanan);
        wadah.prepend(baris);
    };

    /* --------------------------- analisis ------------------------- */
    const analisis = async (file, sumber = 'upload') => {
        if (!file || sedangMemproses) return;

        tampilkanPratinjau(file);
        sembunyikanError();
        aturLoading(true);

        const data = new FormData();
        data.append('file', file);
        data.append('sumber', sumber);

        try {
            const respons = await fetch(urlPrediksi, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    Accept: 'application/json',
                },
                body: data,
            });

            const json = await respons.json().catch(() => ({}));

            if (!respons.ok) {
                const pesanValidasi = json.errors
                    ? Object.values(json.errors).flat()[0]
                    : null;
                throw new Error(pesanValidasi ?? json.message ?? `Gagal menganalisis (${respons.status}).`);
            }

            renderHasil(json.hasil);
            perbaruiStatistik(json.hasil);
            tambahTerbaru(json.hasil);
        } catch (error) {
            tampilkanError(error.message ?? 'Terjadi kesalahan saat menganalisis gambar.');
            resetSemua();
        } finally {
            aturLoading(false);
        }
    };

    const analisisContoh = async (tombol) => {
        if (sedangMemproses) return;

        try {
            const responsGambar = await fetch(tombol.dataset.url);
            if (!responsGambar.ok) throw new Error('Contoh gambar tidak dapat dimuat.');
            const blob = await responsGambar.blob();
            const file = new File([blob], tombol.dataset.nama, { type: blob.type || 'image/jpeg' });

            tampilkanPratinjau(file);
            sembunyikanError();
            aturLoading(true);

            const respons = await fetch(urlContoh, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ label: tombol.dataset.label, nama: tombol.dataset.nama }),
            });

            const json = await respons.json().catch(() => ({}));
            if (!respons.ok) {
                throw new Error(json.message ?? `Gagal menganalisis contoh (${respons.status}).`);
            }

            renderHasil(json.hasil);
            perbaruiStatistik(json.hasil);
            tambahTerbaru(json.hasil);
        } catch (error) {
            tampilkanError(error.message ?? 'Gagal menganalisis contoh.');
            resetSemua();
        } finally {
            aturLoading(false);
        }
    };

    /* -------------------------- interaksi ------------------------- */
    dropzone.addEventListener('click', (event) => {
        if (event.target.closest('button, a, input')) return;
        inputFile?.click();
    });

    dropzone.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            inputFile?.click();
        }
    });

    ['dragenter', 'dragover'].forEach((nama) => {
        dropzone.addEventListener(nama, (event) => {
            event.preventDefault();
            dropzone.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach((nama) => {
        dropzone.addEventListener(nama, (event) => {
            event.preventDefault();
            dropzone.classList.remove('dragover');
        });
    });

    dropzone.addEventListener('drop', (event) => {
        const file = event.dataTransfer?.files?.[0];
        if (file) validasiDanAnalisis(file);
    });

    inputFile?.addEventListener('change', () => {
        const file = inputFile.files?.[0];
        if (file) validasiDanAnalisis(file);
    });

    const validasiDanAnalisis = (file) => {
        sembunyikanError();

        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            tampilkanError('Format gambar harus JPG, PNG, atau WEBP.');
            return;
        }

        if (file.size > 10 * 1024 * 1024) {
            tampilkanError('Ukuran gambar maksimal 10 MB.');
            return;
        }

        analisis(file, 'upload');
    };

    tombolReset?.addEventListener('click', resetSemua);
    analisisLagi?.addEventListener('click', resetSemua);

    document.querySelectorAll('.contoh-item').forEach((tombol) => {
        tombol.addEventListener('click', () => analisisContoh(tombol));
    });

    /* ---------------------------- kamera -------------------------- */
    const modal = pilih('#modal-kamera');
    if (!modal) return;

    const video = pilih('#kamera-video');
    const jepretan = pilih('#kamera-jepretan');
    const status = pilih('#kamera-status');
    const errorKamera = pilih('#kamera-error');
    const errorKameraTeks = pilih('#kamera-error-teks');
    const btnJepret = pilih('#kamera-jepret');
    const btnGunakan = pilih('#kamera-gunakan');
    const btnUlangi = pilih('#kamera-ulangi');
    const btnGanti = pilih('#kamera-ganti');

    let stream = null;
    let facingMode = 'environment';
    let blobJepretan = null;
    let urlJepretan = null;

    const hentikanKamera = () => {
        if (stream) {
            stream.getTracks().forEach((track) => track.stop());
            stream = null;
        }
    };

    const sembunyikanErrorKamera = () => {
        errorKamera?.classList.add('hidden');
        errorKamera?.classList.remove('flex');
    };

    const tampilkanErrorKamera = (pesan) => {
        if (!errorKamera || !errorKameraTeks) return;
        errorKameraTeks.textContent = pesan;
        errorKamera.classList.remove('hidden');
        errorKamera.classList.add('flex');
        btnJepret.disabled = true;
        btnGanti.disabled = true;
    };

    const pesanKamera = (error) => {
        if (!window.isSecureContext) {
            return 'Kamera hanya bisa diakses lewat localhost atau HTTPS. Buka situs melalui http://localhost:8000.';
        }
        if (error?.name === 'NotAllowedError' || error?.name === 'SecurityError') {
            return 'Izin kamera ditolak. Izinkan akses kamera pada pengaturan browser lalu coba lagi.';
        }
        if (error?.name === 'NotFoundError' || error?.name === 'DevicesNotFoundError') {
            return 'Tidak ada kamera yang terdeteksi pada perangkat ini.';
        }
        if (error?.name === 'NotReadableError' || error?.name === 'TrackStartError') {
            return 'Kamera sedang dipakai aplikasi lain. Tutup aplikasi tersebut lalu coba lagi.';
        }
        return 'Kamera tidak dapat dinyalakan. Pastikan perangkat memiliki kamera dan izin browser aktif.';
    };

    const mulaiKamera = async () => {
        hentikanKamera();
        sembunyikanErrorKamera();

        status?.classList.remove('hidden');
        status?.classList.add('flex');
        jepretan?.classList.add('hidden');
        video?.classList.remove('hidden');

        btnJepret?.classList.remove('hidden');
        btnJepret.disabled = true;
        btnGunakan?.classList.add('hidden');
        btnUlangi?.classList.add('hidden');
        btnGanti.disabled = true;

        if (!navigator.mediaDevices?.getUserMedia) {
            status?.classList.add('hidden');
            tampilkanErrorKamera('Browser ini tidak mendukung akses kamera. Gunakan upload gambar sebagai gantinya.');
            return;
        }

        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: { ideal: facingMode },
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                },
                audio: false,
            });

            video.srcObject = stream;
            await video.play().catch(() => {});

            status?.classList.add('hidden');
            status?.classList.remove('flex');
            btnJepret.disabled = false;
            btnGanti.disabled = false;
        } catch (error) {
            status?.classList.add('hidden');
            status?.classList.remove('flex');
            tampilkanErrorKamera(pesanKamera(error));
        }
    };

    const bukaKamera = async () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        await mulaiKamera();
    };

    const tutupKamera = () => {
        hentikanKamera();
        if (urlJepretan) URL.revokeObjectURL(urlJepretan);
        urlJepretan = null;
        blobJepretan = null;

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');

        if (jepretan) jepretan.removeAttribute('src');
    };

    const jepret = () => {
        if (!stream || !video || video.videoWidth === 0) {
            tampilkanErrorKamera('Gambar kamera belum siap. Tunggu sebentar lalu coba lagi.');
            return;
        }

        const kanvas = document.createElement('canvas');
        kanvas.width = video.videoWidth;
        kanvas.height = video.videoHeight;
        kanvas.getContext('2d').drawImage(video, 0, 0);

        kanvas.toBlob((blob) => {
            if (!blob) {
                tampilkanErrorKamera('Gagal mengambil gambar dari kamera.');
                return;
            }

            blobJepretan = blob;
            if (urlJepretan) URL.revokeObjectURL(urlJepretan);
            urlJepretan = URL.createObjectURL(blob);

            if (jepretan) {
                jepretan.src = urlJepretan;
                jepretan.classList.remove('hidden');
            }
            video.classList.add('hidden');

            btnJepret.classList.add('hidden');
            btnGunakan.classList.remove('hidden');
            btnUlangi.classList.remove('hidden');
        }, 'image/jpeg', 0.92);
    };

    const ulangi = () => {
        if (urlJepretan) URL.revokeObjectURL(urlJepretan);
        urlJepretan = null;
        blobJepretan = null;

        jepretan?.classList.add('hidden');
        video?.classList.remove('hidden');
        btnGunakan?.classList.add('hidden');
        btnUlangi?.classList.add('hidden');
        btnJepret?.classList.remove('hidden');
        btnJepret.disabled = false;
    };

    tombolKamera?.addEventListener('click', bukaKamera);
    btnJepret?.addEventListener('click', jepret);
    btnUlangi?.addEventListener('click', ulangi);

    btnGunakan?.addEventListener('click', () => {
        if (!blobJepretan) return;

        const file = new File([blobJepretan], `kamera-${Date.now()}.jpg`, {
            type: 'image/jpeg',
        });

        tutupKamera();
        analisis(file, 'kamera');
    });

    btnGanti?.addEventListener('click', () => {
        facingMode = facingMode === 'environment' ? 'user' : 'environment';
        mulaiKamera();
    });

    modal.querySelectorAll('[data-tutup-kamera]').forEach((el) => {
        el.addEventListener('click', tutupKamera);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            tutupKamera();
        }
    });
}
