<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingRequest extends Model
{
    protected $fillable = [
        'owner_id',
        'property_id',
        'title',
        'description',
        'type_reunion',
        'required_threshold',
        'votes_for',
        'status',
        'meeting_id',
        'triggered_at',
    ];

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}