<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContributionPayment extends Model
{
    protected $fillable = [
        'contribution_id',
        'owner_id',
        'amount',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function contribution()
    {
        return $this->belongsTo(Contribution::class);
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }
}