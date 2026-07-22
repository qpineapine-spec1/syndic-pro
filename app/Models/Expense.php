<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'property_id',
        'service_provider_id',
        'owner_id',
        'label',
        'amount',
        'expense_date',
        'type',
        'status',
        'fichier_facture',
        'categorie',
        'montant_mensuel',
        'justificatif_pdf',
        'paid_at',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'paid_at' => 'datetime',
    ];

    /**
     * Vrai si la dépense a été cochée "payée" pendant le cycle de 30 jours en cours.
     * Le cycle est fourni par Property::currentBillingCycleStart().
     */
    public function isPaidThisCycle(?\Carbon\Carbon $cycleStart): bool
    {
        if (!$this->paid_at || !$cycleStart) {
            return false;
        }

        return $this->paid_at->gte($cycleStart);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function serviceProvider()
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }
}