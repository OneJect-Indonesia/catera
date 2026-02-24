<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Authorized extends Model
{
    /** @use HasFactory<\Database\Factories\AuthorizedFactory> */
    use HasFactory;

    protected $fillable = [
        'uuid',
        'first_name',
        'last_name',
        'group',
        'quota',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
