<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Akun extends Authenticatable
{
    use HasFactory;

    protected $table = 'akun';

    protected $fillable = ['id_role', 'username', 'password'];

    protected $hidden = ['password'];

    protected $primaryKey = 'id_akun';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'id_role', 'id_role');
    }

    public function guru(): HasOne
    {
        return $this->hasOne(Guru::class, 'id_akun', 'id_akun');
    }

    public function tataUsaha(): HasOne
    {
        return $this->hasOne(TataUsaha::class, 'id_akun', 'id_akun');
    }

    public function siswa(): HasOne
    {
        return $this->hasOne(Siswa::class, 'id_akun', 'id_akun');
    }
}
