<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'owner_id',
        'tenant_name',
        'tenant_phone',
        'contract_start_date',
        'contract_end_date',
        'is_active',
        'alert_days_before_end',
        'reminder_sent_at',
    ];

    protected $casts = [
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'reminder_sent_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }
}