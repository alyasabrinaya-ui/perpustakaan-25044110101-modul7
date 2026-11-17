@extends('layouts.app')

@section('title', 'Daftar Buku')

@section('content')
    {{-- ===== Header halaman ===== --}}
    <div class="page-head">
        <h1>Daftar Buku</h1>

        {{-- Soal 3: tombol Tambah hanya tampil untuk yang boleh kelola buku (admin & petugas) --}}
        @can('kelola-buku')
            <a href="{{ route('buku.create') }}" class="btn btn-primary" style="margin-top: .75rem;">+ Tambah Buku</a>
        @endcan
    </div>

    <div id="pesan" class="alert" hidden></div>

    {{-- ===== Tabel daftar buku (diisi lewat JavaScript dari API) ===== --}}
    <section class="panel">
        <div class="table-wrap">
            <table class="tabel-buku">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kategori</th>
                        <th>Judul</th>
                        <th>Penulis</th>
                        <th>Tahun Terbit</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="tabel-body">
                    <tr><td colspan="6" class="tabel-info">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="pager">
            <button type="button" id="btn-prev" class="btn btn-reset">&laquo; Sebelumnya</button>
            <span id="info-halaman"></span>
            <button type="button" id="btn-next" class="btn btn-reset">Berikutnya &raquo;</button>
        </div>
    </section>

    {{-- ===== Buku Terbaru: tempat pembuktian XSS (A1) ===== --}}
    <section class="panel">
        <h2>Buku Terbaru</h2>
        <ul>
            @foreach ($bukuTerbaru as $b)
                <!-- {{-- Soal 1 (A1, RENTAN / XSS): judul tanpa escaping, <script> akan dijalankan browser --}}
                <li>{!! $b->judul !!}</li> -->

                <!-- Soal 2 (a): PERBAIKAN. Blade escaping, judul tampil sebagai teks.
                     Untuk mengaktifkan: comment baris {!! !!} di atas, lalu hapus pembungkus komentar di bawah. -->
                <li>{{ $b->judul }}</li>
            @endforeach
        </ul>
    </section>

    {{-- Soal 2 (c) + Soal 3: form hapus dengan @csrf dan @method('DELETE'),
         hanya untuk yang boleh kelola buku --}}
    @can('kelola-buku')
        <form id="form-hapus" method="POST" hidden>
            @csrf
            @method('DELETE')
        </form>
    @endcan

    <script>
        // ===== Konfigurasi =====
        const API_URL       = @json(url('/api/buku'));
        {{-- Soal 3 (sebelumnya): const BISA_KELOLA = @json(auth()->user()->can('kelola data buku')); --}}
        const BISA_KELOLA   = @can('kelola-buku') true @else false @endcan;
        const HEADER_ACCEPT = { 'Accept': 'application/json' };

        // ===== Elemen halaman =====
        const elPesan = document.getElementById('pesan');
        const tbody   = document.getElementById('tabel-body');
        const btnPrev = document.getElementById('btn-prev');
        const btnNext = document.getElementById('btn-next');
        const infoHal = document.getElementById('info-halaman');

        let halamanSekarang = 1;
        let timerPesan = null;

        // ===== Pesan & penanganan error =====
        function tampilPesan(teks, tipe) {
            clearTimeout(timerPesan);
            elPesan.textContent = teks;
            elPesan.className = 'alert alert-' + tipe;
            elPesan.hidden = false;

            if (tipe === 'sukses') {
                timerPesan = setTimeout(() => { elPesan.hidden = true; }, 3000);
            }
        }

        async function ambilPesanError(response) {
            let body = {};
            try { body = await response.json(); } catch (e) { /* respons bukan JSON */ }

            if (response.status === 422 && body.errors) {
                return Object.values(body.errors).flat().join('\n');
            }
            return body.message || `Permintaan gagal (HTTP ${response.status}).`;
        }

        function pesanDariError(err) {
            if (err instanceof TypeError) {
                return 'API tidak dapat diakses. Pastikan server Laravel berjalan.';
            }
            return err.message;
        }

        // ===== Render tabel =====
        function isiTabelKosong(teks) {
            const tr = document.createElement('tr');
            const td = document.createElement('td');
            td.colSpan = 6;
            td.className = 'tabel-info';
            td.textContent = teks;
            tr.appendChild(td);
            tbody.replaceChildren(tr);
        }

        function renderTabel(data, meta) {
            if (data.length === 0) {
                isiTabelKosong('Belum ada data buku.');
            } else {
                tbody.replaceChildren();

                data.forEach((buku, i) => {
                    const tr = document.createElement('tr');
                    const nomor = (meta.current_page - 1) * meta.per_page + i + 1;

                    // Sel data. Memakai textContent, jadi aman dari XSS.
                    [nomor, buku.kategori, buku.judul, buku.penulis, buku.tahun].forEach((isi, idx) => {
                        const td = document.createElement('td');

                        if (idx === 1) {
                            const chip = document.createElement('span');
                            chip.className = 'chip';
                            chip.textContent = isi;
                            td.appendChild(chip);
                        } else {
                            td.textContent = isi;
                        }

                        tr.appendChild(td);
                    });

                    // Sel aksi
                    const tdAksi = document.createElement('td');
                    tdAksi.className = 'aksi';

                    const btnDetail = document.createElement('a');
                    btnDetail.href = `/buku/${buku.id}`;
                    btnDetail.className = 'btn btn-sm btn-primary';
                    btnDetail.textContent = 'Detail';
                    tdAksi.appendChild(btnDetail);

                    // Soal 3: tombol Ubah & Hapus hanya untuk yang boleh kelola buku
                    if (BISA_KELOLA) {
                        const btnUbah = document.createElement('a');
                        btnUbah.href = `/buku/${buku.id}/edit`;
                        btnUbah.className = 'btn btn-sm btn-warning';
                        btnUbah.textContent = 'Ubah';

                        const btnHapus = document.createElement('button');
                        btnHapus.type = 'button';
                        btnHapus.className = 'btn btn-sm btn-danger';
                        btnHapus.textContent = 'Hapus';
                        btnHapus.addEventListener('click', () => hapusBuku(buku));

                        tdAksi.append(btnUbah, btnHapus);
                    }

                    tr.appendChild(tdAksi);
                    tbody.appendChild(tr);
                });
            }

            infoHal.textContent = `Halaman ${meta.current_page} dari ${meta.last_page} (${meta.total} buku)`;
            btnPrev.disabled = meta.current_page <= 1;
            btnNext.disabled = meta.current_page >= meta.last_page;
        }

        // ===== Memuat data dari API =====
        async function muatBuku(halaman = 1) {
            try {
                const response = await fetch(`${API_URL}?page=${halaman}`, { headers: HEADER_ACCEPT });
                if (!response.ok) {
                    throw new Error(await ambilPesanError(response));
                }

                const hasil = await response.json();

                if (hasil.data.length === 0 && halaman > 1) {
                    return muatBuku(halaman - 1);
                }

                halamanSekarang = hasil.meta.current_page;
                renderTabel(hasil.data, hasil.meta);
            } catch (err) {
                tampilPesan('Gagal memuat data buku. ' + pesanDariError(err), 'gagal');
                isiTabelKosong('Data buku tidak dapat ditampilkan.');
            }
        }

        // ===== Hapus buku =====
        // Soal 2 (c) - kode sebelumnya (hapus lewat fetch DELETE ke API, tanpa form @csrf):
        // async function hapusBuku(buku) {
        //     if (!confirm(`Yakin ingin menghapus buku "${buku.judul}"?`)) {
        //         return;
        //     }
        //
        //     try {
        //         const response = await fetch(`${API_URL}/${buku.id}`, {
        //             method: 'DELETE',
        //             headers: HEADER_ACCEPT,
        //         });
        //
        //         if (!response.ok) {
        //             throw new Error(await ambilPesanError(response));
        //         }
        //
        //         tampilPesan('Buku berhasil dihapus.', 'sukses');
        //         await muatBuku(halamanSekarang);
        //     } catch (err) {
        //         tampilPesan(pesanDariError(err), 'gagal');
        //     }
        // }

        // Soal 2 (c): hapus lewat form DELETE + token CSRF
        function hapusBuku(buku) {
            if (!confirm(`Yakin ingin menghapus buku "${buku.judul}"?`)) {
                return;
            }

            const formHapus = document.getElementById('form-hapus');
            formHapus.action = `/buku/${buku.id}`;
            formHapus.submit();
        }

        // ===== Navigasi halaman =====
        btnPrev.addEventListener('click', () => muatBuku(halamanSekarang - 1));
        btnNext.addEventListener('click', () => muatBuku(halamanSekarang + 1));

        muatBuku(1);
    </script>
@endsection