<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Registered extends Model
{
    protected $fillable = [
        'authorized_uuid',
        'add_quota',
        'target_date',
        'status',
    ];

    protected $casts = [
        'target_date' => 'date',
    ];

    /**
     * Get the authorized associated with the registered record.
     */
    public function authorized(): BelongsTo
    {
        return $this->belongsTo(Authorized::class, 'authorized_uuid', 'uuid');
    }
}
