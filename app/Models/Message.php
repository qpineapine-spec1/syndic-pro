<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'owner_id',
        'property_id',
        'subject',
        'body',
        'sender_type',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Per-owner read markers. A broadcast message (owner_id null) is a
     * single row shared by many owners, so its read state can never be
     * a single boolean — each owner needs their own marker here.
     */
    public function reads()
    {
        return $this->hasMany(MessageRead::class);
    }

    public function isReadByOwner(?int $ownerId): bool
    {
        if (! $ownerId) {
            return false;
        }

        return $this->relationLoaded('reads')
            ? $this->reads->contains('owner_id', $ownerId)
            : $this->reads()->where('owner_id', $ownerId)->exists();
    }

    /**
     * Scope: messages not yet read by the given authenticated user
     * (i.e. sent by "the other side" of the conversation).
     */
    public function scopeUnreadForUser($query, $user)
    {
        if ($user->role === 'syndic') {
            $propertyId = $user->syndic->property_id ?? null;

            return $query->where('property_id', $propertyId)
                ->where('sender_type', 'owner')
                ->where('is_read', false);
        }

        $owner = $user->owner;
        $ownerId = $owner->id ?? null;

        // Owner-side messages may be broadcasts shared with other owners,
        // so "unread" is tracked per-owner via message_reads, not the
        // shared is_read column.
        return $query->where('property_id', $owner->property_id ?? null)
            ->where('sender_type', 'syndic')
            ->where(function ($q) use ($ownerId) {
                $q->whereNull('owner_id')->orWhere('owner_id', $ownerId);
            })
            ->whereDoesntHave('reads', function ($q) use ($ownerId) {
                $q->where('owner_id', $ownerId);
            });
    }
}