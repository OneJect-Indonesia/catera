<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessLog extends Model
{
    protected $table = 'catera.access_logs';

    public $timestamps = false;

    protected $fillable = [
        'authorizeds_id',
        'uuid',
        'group',
        'status',
        'scanned_at',
    ];

    protected function casts(): array
    {
        return [
            'scanned_at' => 'datetime',
        ];
    }

    public function scopeToday(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereBetween('scanned_at', [
            now()->startOfDay(),
            now()->endOfDay(),
        ]);
    }

    /**
     * Get the authorized user associated with the access log.
     */
    public function authorized(): BelongsTo
    {
        return $this->belongsTo(Authorized::class, 'authorizeds_id');
    }
}
