<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    protected $fillable = [
        'property_id',
        'syndic_id',
        'title',
        'meeting_date',
        'agenda',
        'status',
        'type_reunion',
        'lieu',
        'compte_rendu',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function syndic()
    {
        return $this->belongsTo(Syndic::class);
    }

    public function votes()
    {
        return $this->hasMany(Vote::class);
    }
}
