<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Add dynamic attributes for firstname and lastname
    protected $appends = ['firstname', 'lastname'];

    public function getFirstnameAttribute()
    {
        return $this->attributes['firstname'] ?? $this->name; // Fallback to 'name' if not set
    }

    public function getLastnameAttribute()
    {
        return $this->attributes['lastname'] ?? ''; // Default to empty string if not set
    }

    public function setFirstnameAttribute($value)
    {
        $this->attributes['firstname'] = $value;
    }

    public function setLastnameAttribute($value)
    {
        $this->attributes['lastname'] = $value;
    }
}