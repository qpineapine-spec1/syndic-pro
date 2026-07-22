<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class OwnerInvitation extends Model
{
    protected $fillable = [
        'email',
        'phone',
        'property_id',
        'token_hash',
        'expires_at',
        'created_by',
        'used_at',
    ];

    protected $dates = [
        'expires_at',
        'used_at',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isValid(): bool
    {
        if ($this->used_at) {
            return false;
        }

        return Carbon::now()->lte($this->expires_at);
    }

    public function scopeValid($query)
    {
        return $query->whereNull('used_at')->where('expires_at', '>=', Carbon::now());
    }
}
