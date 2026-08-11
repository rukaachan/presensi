<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 */
class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $fillable = ['code', 'name'];

    public $timestamps = false;

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'role_id');
    }
}
