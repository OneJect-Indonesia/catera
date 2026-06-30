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

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
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
     * Get the quota schedule record associated with the authorized.
     */
    public function quotaSchedule(): HasOne
    {
        return $this->hasOne(QuotaSchedule::class, 'authorized_uuid', 'uuid');
    }

    // public static function getGloballySearchableAttributes(): array
    // {
    //     return ['uuid', 'group', 'user.first_name', 'user.last_name'];
    // }
}
