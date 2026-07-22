<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    protected $fillable = [
        'property_id',
        'syndic_id',
        'year',
        'prediction_xgboost',
        'is_valid',
        'fixed_charges_total',
        'variable_charges_total',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function syndic()
    {
        return $this->belongsTo(Syndic::class);
    }

    public function contributions()
    {
        return $this->hasMany(Contribution::class);
    }
}
