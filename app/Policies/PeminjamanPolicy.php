<?php

namespace App\Policies;

use App\Models\Peminjaman;
use App\Models\User;

/**
 * Soal 4: Policy untuk menutup celah A4 (IDOR).
 *
 * Policy ditemukan otomatis oleh Laravel karena namanya mengikuti pola
 * App\Models\Peminjaman -> App\Policies\PeminjamanPolicy.
 * Dipanggil lewat Gate::authorize('view', $peminjaman) di PeminjamanController@show.
 */
class PeminjamanPolicy
{
    /**
     * Menentukan apakah pengguna boleh melihat detail satu peminjaman.
     *
     * - admin dan petugas : boleh melihat semua peminjaman
     * - siswa             : hanya peminjaman miliknya sendiri
     */
    public function view(User $user, Peminjaman $peminjaman): bool
    {
        // Admin dan petugas boleh melihat semua peminjaman.
        if ($user->hasAnyRole(['admin', 'petugas'])) {
            return true;
        }

        // Siswa hanya boleh melihat peminjaman yang anggotanya adalah dirinya.
        return $peminjaman->anggota?->user_id === $user->id;
    }
}