<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Surat extends Model
{
    use HasFactory;

    protected $table = 'surat_keterangan';

    protected $fillable = ['id_presensi', 'surat_keterangan'];

    protected $primaryKey = 'id_presensi';

    public $timestamps = false;

    public function presensiSiswa(): BelongsTo
    {
        return $this->belongsTo(PresensiSiswa::class, 'id_presensi', 'id_presensi');
    }
}
