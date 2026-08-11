<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class PresensiSiswa extends Model
{
    use HasFactory;

    protected $table = 'presensi_siswa';

    protected $fillable = ['id_siswa', 'foto_bukti', 'jam_masuk', 'tanggal', 'status_kehadiran', 'keterangan', 'pembuat'];

    protected $primaryKey = 'id_presensi';

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected function tanggal(): Attribute
    {
        return Attribute::make(
            get: static fn (mixed $value) => $value === null ? null : Carbon::parse((string) $value),
            set: static function (mixed $value): ?string {
                if ($value === null) {
                    return null;
                }

                if ($value instanceof CarbonInterface) {
                    return $value->toDateString();
                }

                return Carbon::parse((string) $value)->toDateString();
            },
        );
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }

    public function attendanceRecord(): HasOne
    {
        return $this->hasOne(AttendanceRecord::class, 'legacy_presensi_id', 'id_presensi');
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
