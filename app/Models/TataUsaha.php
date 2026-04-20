<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TataUsaha extends Model
{
    use HasFactory;

    protected $table = 'tata_usaha';

    protected $fillable = ['id_akun', 'nama_tata_usaha', 'foto_tata_usaha'];

    protected $primaryKey = 'id_tata_usaha';

    public $timestamps = false;

    public function akun(): BelongsTo
    {
        return $this->belongsTo(Akun::class, 'id_akun', 'id_akun');
    }
}
