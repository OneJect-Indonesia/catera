<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Authorized extends Model
{
    /** @use HasFactory<\Database\Factories\AuthorizedFactory> */
    use HasFactory;

    protected $fillable = [
        'uuid',
        'nik',
        'first_name',
        'last_name',
        'group',
        'quota',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the registered record associated with the authorized.
     */
    public function registered(): HasOne
    {
        return $this->hasOne(Registered::class, 'authorized_uuid', 'uuid');
    }
}
