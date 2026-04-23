<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unauthorized extends Model
{
    /** @use HasFactory<\Database\Factories\UnauthorizedFactory> */
    use HasFactory;

    protected $table = 'catera.unauthorizeds';

    protected $fillable = ['uuid'];
}
