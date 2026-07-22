<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoteParticipation extends Model
{
    protected $fillable = [
        'owner_id',
        'vote_choice_id',
        'participated_at',
    ];

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function voteChoice()
    {
        return $this->belongsTo(VoteChoice::class);
    }
}
