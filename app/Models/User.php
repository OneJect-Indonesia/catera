<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles;

    protected $table = 'portal_application.md_users';

    protected $fillable = [
        'nik',
        'first_name',
        'last_name',
        'department_id',
        'status',
        'name',
        'email',
        'password',
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
     * Get/Set the user's full name.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => trim($this->first_name.' '.$this->last_name),
            set: function ($value) {
                $parts = explode(' ', $value, 2);

                return [
                    'first_name' => $parts[0] ?? '',
                    'last_name' => $parts[1] ?? '',
                ];
            }
        );
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
