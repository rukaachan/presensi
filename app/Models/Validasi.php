<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Validasi extends Model
{
    use HasFactory;

    protected $table = 'validasi';

    protected $fillable = ['id_pengurus', 'id_presensi', 'status_validasi', 'waktu_validasi'];

    protected $primaryKey = 'id_validasi';

    public $timestamps = false;

    public function pengurusKelas(): BelongsTo
    {
        return $this->belongsTo(PengurusKelas::class, 'id_pengurus', 'id_pengurus');
    }

    public function presensiSiswa(): BelongsTo
    {
        return $this->belongsTo(PresensiSiswa::class, 'id_presensi', 'id_presensi');
    }
}
