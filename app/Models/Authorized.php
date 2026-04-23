<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Authorized extends Model
{
    /** @use HasFactory<\Database\Factories\AuthorizedFactory> */
    use HasFactory;

    protected $table = 'catera.authorizeds';

    protected $fillable = [
        'user_id',
        'uuid',
        'group',
        'quota',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the portal user associated with this authorized record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the registered record associated with the authorized.
     */
    public function registered(): HasOne
    {
        return $this->hasOne(Registered::class, 'authorized_uuid', 'uuid');
    }
}
