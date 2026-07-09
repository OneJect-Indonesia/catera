<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MdGroup extends Model
{
    /** @use HasFactory<\Database\Factories\MdGroupFactory> */
    use HasFactory;

    protected $table = 'catera.md_groups';

    protected $fillable = [
        'nama_group',
        'short_description',
        'color',
    ];

    public function authorizeds(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Authorized::class, 'group_id');
    }
}
