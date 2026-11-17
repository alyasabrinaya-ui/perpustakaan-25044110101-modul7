<?php

use App\Http\Controllers\Admin\PenggunaController;
use App\Http\Controllers\BukuController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PeminjamanController;
use App\Http\Controllers\PengembalianController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RiwayatController;
use Illuminate\Support\Facades\Route;


Route::view('/', 'beranda')->name('home');

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', function () {
        $user = auth()->user();

        if ($user->can('akses dashboard admin')) {
            return redirect()->route('admin.dashboard');
        }
        if ($user->can('akses dashboard petugas')) {
            return redirect()->route('petugas.dashboard');
        }

        return redirect()->route('buku.index');
    })->name('dashboard');


    Route::view('/admin/dashboard', 'dashboard.admin')
        ->middleware('permission:akses dashboard admin')->name('admin.dashboard');
    Route::view('/petugas/dashboard', 'dashboard.petugas')
        ->middleware('permission:akses dashboard petugas')->name('petugas.dashboard');

    // =====================================================================
    // SOAL 1 (A3, RENTAN CSRF): hapus buku lewat GET, tanpa token CSRF.
    // Dipicu oleh tautan di jebakan.html.
    // =====================================================================
    // Route::get('/buku/{buku}/hapus', [BukuController::class, 'destroy']);

    // =====================================================================
    // SOAL 2 (c): PERBAIKAN A3 (aktifkan saat mengerjakan Soal 2)
    // Langkah: (1) comment route GET di atas, (2) hapus tanda // di bawah.
    // Penghapusan lewat DELETE + @csrf/@method('DELETE') pada form.
    // Sudah dilindungi Gate 'kelola-buku' (Soal 3).
    // =====================================================================
    Route::delete('/buku/{buku}', [BukuController::class, 'destroy'])
        ->middleware('can:kelola-buku')
        ->name('buku.destroy');

    // === Soal 3: Gate 'kelola-buku' (didefinisikan di AppServiceProvider) ===
    // Grup ini WAJIB di atas route '/buku/{id}' agar 'create' tidak dianggap {id}.
    // Mengganti pemeriksaan permission 'kelola data buku' yang dulu dipakai.
    Route::middleware('can:kelola-buku')->group(function () {
        Route::get('/buku/create', [BukuController::class, 'create'])->name('buku.create');
        Route::post('/buku', [BukuController::class, 'store'])->name('buku.store');
        Route::get('/buku/{buku}/edit', [BukuController::class, 'edit'])->name('buku.edit');
        Route::put('/buku/{buku}', [BukuController::class, 'update'])->name('buku.update');
    });

    Route::middleware('permission:lihat daftar buku')->group(function () {
        Route::get('/buku', [BukuController::class, 'index'])->name('buku.index');
        Route::get('/buku/{id}', [BukuController::class, 'show'])->name('buku.show');
    });

    // === Soal 1 (A2): route /buku-cari di balik middleware auth ===
    // Rentan di sisi controller (SQL digabung), ditambal di Soal 2 (b).
    Route::get('/buku-cari', [BukuController::class, 'cari']);

    // === Soal 1 (A4): route detail peminjaman ===
    // Rentan (IDOR) bila method show hanya findOrFail; ditambal di Soal 4
    // dengan Gate::authorize + Policy.
    Route::get('/peminjaman/{id}', [PeminjamanController::class, 'show']);

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->middleware('permission:lihat profil sendiri')->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])
        ->middleware('permission:ubah profil sendiri')->name('profile.update');

    Route::post('/peminjaman', [PeminjamanController::class, 'store'])
        ->middleware('permission:mengajukan peminjaman buku')->name('peminjaman.store');
    Route::get('/riwayat/{anggota}', [RiwayatController::class, 'show'])
        ->middleware('permission:melihat status peminjaman sendiri|melihat riwayat peminjaman sendiri')
        ->name('riwayat.show');


    Route::middleware('permission:kelola peminjaman')->group(function () {
        Route::get('/peminjaman', [PeminjamanController::class, 'index'])->name('peminjaman.index');
        Route::patch('/peminjaman/{peminjaman}/setujui', [PeminjamanController::class, 'setujui'])
            ->middleware('permission:memproses persetujuan peminjaman')->name('peminjaman.setujui');
    });


    Route::middleware('permission:kelola pengembalian')->group(function () {
        Route::get('/pengembalian', [PengembalianController::class, 'index'])->name('pengembalian.index');
        Route::patch('/pengembalian/{peminjaman}', [PengembalianController::class, 'update'])
            ->middleware('permission:mencatat pengembalian buku')->name('pengembalian.update');
    });


    Route::get('/laporan', [LaporanController::class, 'index'])
        ->middleware('permission:lihat laporan perpustakaan')->name('laporan.index');


    Route::middleware('permission:kelola data pengguna')->prefix('admin/pengguna')->name('admin.pengguna.')->group(function () {
        Route::get('/', [PenggunaController::class, 'index'])->name('index');
        Route::get('/create', [PenggunaController::class, 'create'])->name('create');
        Route::post('/', [PenggunaController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [PenggunaController::class, 'edit'])->name('edit');
        Route::put('/{user}', [PenggunaController::class, 'update'])->name('update');
        Route::delete('/{user}', [PenggunaController::class, 'destroy'])->name('destroy');
        Route::patch('/{user}/role', [PenggunaController::class, 'updateRole'])
            ->middleware('permission:kelola role pengguna')->name('role');
    });
});

require __DIR__.'/auth.php';

// === Soal 5 (a): route sementara untuk menguji APP_DEBUG=false ===
// Sudah dipakai untuk tangkapan layar, lalu dinonaktifkan sesuai ketentuan.
// Route::get('/uji-error', function () {
//     throw new \RuntimeException('Uji error 25044110101');
// });