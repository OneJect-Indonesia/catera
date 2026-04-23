<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    protected $table = 'portal_application.users';

    protected $fillable = [
        'nik',
        'first_name',
        'last_name',
        'department_id',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials from first and last name.
     */
    public function initials(): string
    {
        return Str::of($this->first_name.' '.$this->last_name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get the authorized record associated with this user.
     */
    public function authorizedRecord(): HasOne
    {
        return $this->hasOne(Authorized::class, 'user_id');
    }
}
