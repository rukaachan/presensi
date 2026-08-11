<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $code
 * @property string $label
 * @property string $kind
 * @property bool $required
 * @property bool $active
 */
class AttendanceSession extends Model
{
    use HasFactory;

    protected $table = 'attendance_sessions';

    protected $fillable = [
        'code',
        'label',
        'kind',
        'required',
        'active',
        'window_start',
        'window_end',
        'sort_order',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeRequired(Builder $query): Builder
    {
        return $query->where('required', true);
    }

    public function scopeOptional(Builder $query): Builder
    {
        return $query->where('required', false);
    }
}
