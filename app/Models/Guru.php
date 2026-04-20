<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Guru extends Model
{
    use HasFactory;

    protected $table = 'guru';

    protected $fillable = ['id_akun', 'nama_guru', 'foto_guru', 'pembuat'];

    protected $primaryKey = 'id_guru';

    public $timestamps = false;

    public function akun(): BelongsTo
    {
        return $this->belongsTo(Akun::class, 'id_akun', 'id_akun');
    }

    public function kelasWali(): HasMany
    {
        return $this->hasMany(Kelas::class, 'id_wali_kelas', 'id_guru');
    }

    public function guruPiket(): HasOne
    {
        return $this->hasOne(GuruPiket::class, 'id_guru', 'id_guru');
    }

    public function guruBk(): HasOne
    {
        return $this->hasOne(GuruBk::class, 'id_guru', 'id_guru');
    }
}
