<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // === Soal 3: Gate pusat aturan pengelolaan buku (hanya admin dan petugas) ===
        Gate::define('kelola-buku', function (User $user): bool {
            return $user->hasAnyRole(['admin', 'petugas']);
        });
    }
}