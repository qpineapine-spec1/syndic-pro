<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contribution extends Model
{
    protected $fillable = [
        'owner_id',
        'budget_id',
        'tantieme',
        'quote_part_terrain',
        'montant_annuel',
        'montant_mensuel',
        'charges_surplus',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(ContributionPayment::class);
    }

    /**
     * Statut réel de la cotisation pour le cycle en cours (30 jours, ancré sur imported_at) :
     * - 'a_jour'     : cochée payée pendant le cycle en cours.
     * - 'en_retard'  : pas payée et le cycle a commencé il y a 5 jours ou plus.
     * - 'en_attente' : pas payée, mais encore dans le délai de grâce de 5 jours (statut par défaut).
     */
    public function computeStatus(?\Carbon\Carbon $cycleStart): string
    {
        if ($cycleStart && $this->paid_at && $this->paid_at->gte($cycleStart)) {
            return 'a_jour';
        }

        if ($cycleStart && now()->diffInDays($cycleStart) >= 5) {
            return 'en_retard';
        }

        return 'en_attente';
    }
}