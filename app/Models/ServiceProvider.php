<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceProvider extends Model
{
    protected $fillable = [
        'property_id',
        'name',
        'specialty',
        'contract_reference',
        'contract_start_date',
        'contract_end_date',
        'alert_expiration_days',
        'expiration_alert_sent_at',
        'status',
    ];

    protected $casts = [
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'expiration_alert_sent_at' => 'datetime',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
