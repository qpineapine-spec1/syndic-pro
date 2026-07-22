<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'role',
        'is_council_member',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_council_member' => 'boolean',
    ];

    public function syndic()
    {
        return $this->hasOne(Syndic::class);
    }

    public function owner()
    {
        return $this->hasOne(Owner::class);
    }

    public function accountActivationTokens()
    {
        return $this->hasMany(AccountActivationToken::class);
    }
}
