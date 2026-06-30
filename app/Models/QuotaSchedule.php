<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotaSchedule extends Model
{
    /** @use HasFactory<\Database\Factories\QuotaScheduleFactory> */
    use HasFactory;

    protected $table = 'catera.quota_schedules';

    protected $fillable = [
        'authorized_uuid',
        'add_quota',
        'target_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
        ];
    }

    public function scopeCurrentMonth(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereBetween('target_date', [
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString(),
        ]);
    }

    public function scopeInDateRange(\Illuminate\Database\Eloquent\Builder $query, ?string $startDate, ?string $endDate): \Illuminate\Database\Eloquent\Builder
    {
        return $query->when($startDate, fn ($q) => $q->where('target_date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('target_date', '<=', $endDate));
    }

    /**
     * Get the authorized associated with the quota schedule record.
     */
    public function authorized(): BelongsTo
    {
        return $this->belongsTo(Authorized::class, 'authorized_uuid', 'uuid');
    }
}
