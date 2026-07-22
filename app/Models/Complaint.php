<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    protected $fillable = [
        'owner_id',
        'property_id',
        'subject',
        'description',
        'status',
        'validated_at',
        'priority',
        'fichier_joint',
        'category',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
        'validated_at' => 'datetime',
    ];

    public const STATUS_LABELS = [
        'nouvelle' => 'Nouvelle',
        'en_cours' => 'En cours',
        'finie' => 'Terminée (à valider)',
        'validee' => 'Validée par le copropriétaire',
        'annulee' => 'Annulée',
        'open' => 'Nouvelle',
        'in_progress' => 'En cours',
        'closed' => 'Terminée (à valider)',
    ];

    public const PRIORITY_LABELS = [
        'faible' => 'Faible',
        'normale' => 'Normale',
        'elevee' => 'Élevée',
        'normal' => 'Normale',
    ];

    public const CATEGORY_LABELS = [
        'eau' => 'Eau',
        'electricite' => 'Électricité',
        'ascenseur' => 'Ascenseur',
        'autre' => 'Autre',
    ];

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function priorityLabel(): string
    {
        return self::PRIORITY_LABELS[$this->priority] ?? ucfirst((string) $this->priority);
    }

    public function categoryLabel(): string
    {
        return self::CATEGORY_LABELS[$this->category] ?? ucfirst((string) $this->category);
    }

    public function isValidatedByOwner(): bool
    {
        return $this->status === 'validee' && $this->validated_at !== null;
    }

    public function canBeValidatedByOwner(): bool
    {
        return $this->status === 'finie';
    }
}