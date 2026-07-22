<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoteChoice extends Model
{
    protected $fillable = [
        'vote_id',
        'label',
        'description',
    ];

    public function vote()
    {
        return $this->belongsTo(Vote::class);
    }

    public function voteParticipations()
    {
        return $this->hasMany(VoteParticipation::class);
    }
}
