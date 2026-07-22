<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingRequestVote extends Model
{
    protected $fillable = [
        'meeting_request_id',
        'owner_id',
    ];

    public function meetingRequest()
    {
        return $this->belongsTo(MeetingRequest::class);
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }
}
