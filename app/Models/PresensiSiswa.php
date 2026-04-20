<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PresensiSiswa extends Model
{
    use HasFactory;

    protected $table = 'presensi_siswa';

    protected $fillable = ['id_siswa', 'foto_bukti', 'jam_masuk', 'tanggal', 'status_kehadiran', 'keterangan', 'pembuat'];

    protected $primaryKey = 'id_presensi';

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }

    public function validasis(): HasMany
    {
        return $this->hasMany(Validasi::class, 'id_presensi', 'id_presensi');
    }

    public function surats(): HasMany
    {
        return $this->hasMany(Surat::class, 'id_presensi', 'id_presensi');
    }

    public function scopeJoinSiswa(Builder $query): Builder
    {
        return $query->join('siswa', 'presensi_siswa.id_siswa', '=', 'siswa.id_siswa');
    }

    public function scopeJoinSiswaKelas(Builder $query): Builder
    {
        return $query
            ->join('siswa', 'presensi_siswa.id_siswa', '=', 'siswa.id_siswa')
            ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas');
    }

    public function scopeJoinSiswaKelasGuruWali(Builder $query): Builder
    {
        return $query
            ->join('siswa', 'presensi_siswa.id_siswa', '=', 'siswa.id_siswa')
            ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
            ->join('guru', 'guru.id_guru', '=', 'kelas.id_wali_kelas');
    }

    public function scopeJoinSiswaKelasGuruWaliJurusan(Builder $query): Builder
    {
        return $query
            ->join('siswa', 'presensi_siswa.id_siswa', '=', 'siswa.id_siswa')
            ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
            ->join('guru', 'guru.id_guru', '=', 'kelas.id_wali_kelas')
            ->join('jurusan', 'kelas.id_jurusan', '=', 'jurusan.id_jurusan');
    }
}
