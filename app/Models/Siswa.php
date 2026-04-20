<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Siswa extends Model
{
    use HasFactory;

    protected $table = 'siswa';

    protected $fillable = ['id_akun', 'id_kelas', 'nis', 'nama_siswa', 'nomer_hp', 'jenis_kelamin', 'foto_siswa', 'angkatan', 'status_siswa', 'status_jabatan', 'pembuat'];

    protected $primaryKey = 'id_siswa';

    public $timestamps = false;

    public function akun(): BelongsTo
    {
        return $this->belongsTo(Akun::class, 'id_akun', 'id_akun');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'id_kelas', 'id_kelas');
    }

    public function presensiSiswas(): HasMany
    {
        return $this->hasMany(PresensiSiswa::class, 'id_siswa', 'id_siswa');
    }

    public function pengurusKelas(): HasMany
    {
        return $this->hasMany(PengurusKelas::class, 'id_siswa', 'id_siswa');
    }

    public function scopeJoinKelas(Builder $query): Builder
    {
        return $query->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas');
    }

    public function scopeJoinKelasGuruWali(Builder $query): Builder
    {
        return $query
            ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
            ->join('guru', 'guru.id_guru', '=', 'kelas.id_wali_kelas');
    }
}
