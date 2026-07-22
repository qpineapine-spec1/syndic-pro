<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Owner extends Model
{
    protected $fillable = [
        'property_id',
        'user_id',
        'status',
        'is_tenant',
        'lot_surface',
        'surface_confirmation',
        'has_mezzanine',
        'mezzanine_surface',
        'is_council_member',
        'office_number',
        'floor',
        'telephone',
        'real_owner_name',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tenant()
    {
        return $this->hasOne(Tenant::class);
    }

    public function contributions()
    {
        return $this->hasMany(Contribution::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }

    public function voteParticipations()
    {
        return $this->hasMany(VoteParticipation::class);
    }

    public function meetingRequests()
    {
        return $this->hasMany(MeetingRequest::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
