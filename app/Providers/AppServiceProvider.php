<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Builder::macro('joinKelas', function (): Builder {
            /** @var Builder $this */
            return $this->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas');
        });

        Builder::macro('joinKelasGuruWali', function (): Builder {
            /** @var Builder $this */
            return $this->joinKelas()
                ->join('guru', 'guru.id_guru', '=', 'kelas.id_wali_kelas');
        });

        Builder::macro('joinSiswa', function (): Builder {
            /** @var Builder $this */
            return $this->join('siswa', 'presensi_siswa.id_siswa', '=', 'siswa.id_siswa');
        });

        Builder::macro('joinSiswaKelas', function (): Builder {
            /** @var Builder $this */
            return $this->joinSiswa()
                ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas');
        });

        Builder::macro('joinSiswaKelasGuruWali', function (): Builder {
            /** @var Builder $this */
            return $this->joinSiswaKelas()
                ->join('guru', 'guru.id_guru', '=', 'kelas.id_wali_kelas');
        });

        Builder::macro('joinSiswaKelasGuruWaliJurusan', function (): Builder {
            /** @var Builder $this */
            return $this->joinSiswaKelasGuruWali()
                ->join('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan');
        });
    }
}
