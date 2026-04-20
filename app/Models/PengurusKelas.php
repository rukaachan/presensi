<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PengurusKelas extends Model
{
    use HasFactory;

    protected $table = 'pengurus_kelas';

    protected $fillable = ['id_siswa', 'jabatan', 'pembuat'];

    protected $primaryKey = 'id_pengurus';

    public $timestamps = false;

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }

    public function validasis(): HasMany
    {
        return $this->hasMany(Validasi::class, 'id_pengurus', 'id_pengurus');
    }
}
