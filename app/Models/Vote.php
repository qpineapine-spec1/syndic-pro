<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    protected $fillable = [
        'meeting_id',
        'question',
        'status',
        'starts_at',
        'ends_at',
        'nb_choix_autorises',
        'final_decision',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function voteChoices()
    {
        return $this->hasMany(VoteChoice::class);
    }
}
