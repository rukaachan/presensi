<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $table = 'role_akun';

    protected $fillable = ['nama_role'];

    protected $primaryKey = 'id_role';

    public $timestamps = false;

    public function akuns(): HasMany
    {
        return $this->hasMany(Akun::class, 'id_role', 'id_role');
    }
}
