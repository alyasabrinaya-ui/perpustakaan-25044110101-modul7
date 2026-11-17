<?php

namespace Database\Seeders;

use App\Models\Buku;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Soal 5 (c) - kode sebelumnya (password bawaan 'password' untuk semua akun):
        // $akun = [
        //     ['Admin Perpustakaan',   'admin@gmail.com',   'admin'],
        //     ['Petugas Perpustakaan', 'petugas@gmail.com', 'petugas'],
        //     ['Siswa Satu',           'siswa1@gmail.com',  'siswa'],
        //     ['Siswa Dua',            'siswa2@gmail.com',  'siswa'],
        // ];

        // Soal 5 (c): password admin diambil dari .env (ADMIN_PASSWORD), bila kosong dibuat acak,
        // sehingga password bawaan 'password' tidak lagi bisa dipakai login sebagai admin.
        $akun = [
            ['Admin Perpustakaan',   'admin@gmail.com',   'admin',   env('ADMIN_PASSWORD') ?: Str::random(24)],
            ['Petugas Perpustakaan', 'petugas@gmail.com', 'petugas', 'password'],
            ['Siswa Satu',           'siswa1@gmail.com',  'siswa',   'password'],
            ['Siswa Dua',            'siswa2@gmail.com',  'siswa',   'password'],
        ];

        // foreach ($akun as [$nama, $email, $role]) {
        foreach ($akun as [$nama, $email, $role, $password]) {
            $user = User::updateOrCreate(['email' => $email], [
                'name'     => $nama,
                // 'password' => 'password',
                'password' => $password,
            ]);
            $user->syncRoles($role);
            $user->buatAnggota();
        }

        $buku = Buku::orderBy('id')->take(4)->pluck('id');
        if ($buku->count() < 4) {
            return;
        }

        $siswa1 = User::where('email', 'siswa1@gmail.com')->first()->anggota;
        $siswa2 = User::where('email', 'siswa2@gmail.com')->first()->anggota;

        if ($siswa1->peminjamans()->doesntExist()) {
            $siswa1->peminjamans()->createMany([
                ['buku_id' => $buku[0], 'tanggal_pinjam' => now()->subDays(20)->toDateString(), 'tanggal_kembali' => now()->subDays(10)->toDateString(), 'status' => 'dikembalikan'],
                ['buku_id' => $buku[1], 'tanggal_pinjam' => now()->subDays(3)->toDateString(),  'tanggal_kembali' => null, 'status' => 'dipinjam'],
                ['buku_id' => $buku[2], 'tanggal_pinjam' => now()->toDateString(),              'tanggal_kembali' => null, 'status' => 'diajukan'],
            ]);
        }

        if ($siswa2->peminjamans()->doesntExist()) {
            $siswa2->peminjamans()->create([
                'buku_id' => $buku[3], 'tanggal_pinjam' => now()->subDays(5)->toDateString(), 'tanggal_kembali' => null, 'status' => 'dipinjam',
            ]);
        }
    }
}