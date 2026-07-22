<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'owner_id',
        'user_id',
        'property_id',
        'channel',
        'title',
        'message',
        'is_sent',
        'is_read',
        'sent_at',
    ];

    protected $casts = [
        'is_sent' => 'boolean',
        'is_read' => 'boolean',
        'sent_at' => 'datetime',
    ];

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: notifications visible to the given authenticated user,
     * whether targeted directly via user_id (syndic) or via owner_id (copropriétaire).
     */
    public function scopeForUser($query, $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id);

            if ($user->role === 'copropriétaire' && $user->owner) {
                $q->orWhere('owner_id', $user->owner->id);
            }
        });
    }
}
