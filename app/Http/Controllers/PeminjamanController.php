<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PeminjamanController extends Controller
{
    public function index(): View
    {
        return view('peminjaman.index', [
            'daftar' => Peminjaman::with(['anggota', 'buku'])->orderByDesc('id')->get(),
        ]);
    }

    // =====================================================================
    // SOAL 1 (A4, RENTAN / IDOR) + SOAL 4 (PERBAIKAN)
    // =====================================================================
    public function show($id): View
    {
        // Soal 1 (A4, RENTAN): hanya findOrFail($id), tanpa pemeriksaan pemilik.
        // Siapa pun yang login bisa membuka peminjaman milik orang lain lewat id.
        $peminjaman = Peminjaman::findOrFail($id);

        // Soal 4 (PERBAIKAN): pemeriksaan lewat PeminjamanPolicy::view
        // (admin/petugas boleh semua, siswa hanya miliknya sendiri).
        // Hapus tanda // pada baris di bawah untuk mengaktifkan.
        Gate::authorize('view', $peminjaman);

        return view('peminjaman.show', ['peminjaman' => $peminjaman]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'buku_id' => ['required', 'exists:bukus,id'],
        ]);

        $request->user()->buatAnggota()->peminjamans()->create([
            'buku_id'        => $data['buku_id'],
            'tanggal_pinjam' => now()->toDateString(),
            'status'         => 'diajukan',
        ]);

        return back()->with('sukses', 'Pengajuan peminjaman berhasil dikirim.');
    }

    public function setujui(Peminjaman $peminjaman): RedirectResponse
    {
        $peminjaman->update(['status' => 'dipinjam', 'tanggal_pinjam' => now()->toDateString()]);

        return back()->with('sukses', 'Peminjaman disetujui.');
    }
}