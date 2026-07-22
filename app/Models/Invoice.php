<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'contribution_id',
        'owner_id',
        'property_id',
        'invoice_number',
        'issue_date',
        'due_date',
        'amount',
        'status',
        'payment_date',
        'reminder_sent_at',
        'unpaid_reminder_sent_at',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'payment_date' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'unpaid_reminder_sent_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function contribution()
    {
        return $this->belongsTo(Contribution::class);
    }
}