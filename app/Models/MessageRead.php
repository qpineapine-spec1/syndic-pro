<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageRead extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'message_id',
        'owner_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }
}