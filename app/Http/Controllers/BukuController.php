<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBukuRequest;
use App\Http\Requests\UpdateBukuRequest;
use App\Models\Buku;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class BukuController extends Controller
{
    private function formatBuku(Buku $buku): array
    {
        return [
            'id'           => $buku->id,
            'judul'        => $buku->judul,
            'penulis'      => $buku->penulis,
            'tahun_terbit' => (int) $buku->tahun_terbit,
            'kategori'     => $buku->kategori->nama ?? '-',
        ];
    }

    public function index()
    
    {
        return view('buku.index', [
            'daftarKategori' => Kategori::orderBy('nama')->get(['id', 'nama']),
            'bukuTerbaru'    => Buku::orderByDesc('id')->take(5)->get(['id', 'judul']),
        ]);
    }

    public function show($id)
    {
        $record = Buku::with('kategori')->find((int) $id);
        $buku   = $record ? $this->formatBuku($record) : null;

        return response()->view('buku.show', [
            'buku' => $buku,
            'id'   => $id,
        ], $buku ? 200 : 404);
    }


    
    // === Soal 3: create/store/edit/update dilindungi middleware can:kelola-buku di routes/web.php ===
    public function create()
    {
        return view('buku.form', [
            'buku'           => new Buku(),
            'daftarKategori' => Kategori::orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function store(StoreBukuRequest $request)
    {
        Buku::create($request->validated());

        return redirect()->route('buku.index')->with('sukses', 'Buku berhasil ditambahkan.');
    }

    public function edit(Buku $buku)
    {
        return view('buku.form', [
            'buku'           => $buku,
            'daftarKategori' => Kategori::orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function update(UpdateBukuRequest $request, Buku $buku)
    {
        $buku->update($request->validated());

        return redirect()->route('buku.index')->with('sukses', 'Buku berhasil diperbarui.');
    }

    // === Soal 1 (A2) + Soal 2 (b) ===
    public function cari(Request $request)
    {
        $judul = $request->query('judul');

        // Soal 1 (A2, RENTAN): nilai judul digabung langsung ke string SQL -> SQL Injection
        // $hasil = DB::select("SELECT * FROM bukus WHERE judul = '$judul'");

        // Soal 2 (b): query dengan binding, input diperlakukan sebagai data, bukan bagian SQL
        $hasil = DB::select('SELECT * FROM bukus WHERE judul = ?', [$judul]);

        return response()->json($hasil);
    }

    // === Soal 1 (A3) + Soal 2 (c) + Soal 3 ===
    // Soal 1 (A3, RENTAN): method ini dulu dipanggil lewat GET /buku/{buku}/hapus (tanpa CSRF).
    // Soal 2 (c): sekarang dipanggil lewat DELETE /buku/{buku} dengan @csrf + @method('DELETE').
    // Soal 3: route dilindungi can:kelola-buku.
    public function destroy(Buku $buku)
    {
        if ($buku->peminjamans()->exists()) {
            return redirect()->route('buku.index')
                ->with('gagal', 'Buku masih punya riwayat peminjaman, tidak bisa dihapus.');
        }

        $buku->delete();

        return redirect()->route('buku.index')->with('sukses', 'Buku berhasil dihapus.');
    }
}