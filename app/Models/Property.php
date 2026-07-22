<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    protected $fillable = [
        'name',
        'address',
        'city',
        'postal_code',
        'description',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
    ];

    public function hasCompletedFirstAssembly(): bool
    {
        return !is_null($this->imported_at) && $this->imported_at->lte(now());
    }

    /**
     * Début du cycle de facturation en cours (30 jours), ancré sur le jour du mois
     * où le PDF de la première assemblée a été importé (imported_at).
     * Ex: import le 4 janvier -> le cycle "recommence" le 4 de chaque mois.
     * Si le mois courant n'a pas ce jour (ex: jour 31 en février), on prend
     * le dernier jour du mois.
     */
    public function currentBillingCycleStart(): ?\Carbon\Carbon
    {
        if (!$this->imported_at) {
            return null;
        }

        $anchorDay = $this->imported_at->day;
        $today = now();

        $candidate = self::clampedCycleDate($today->year, $today->month, $anchorDay);

        if ($candidate->gt($today)) {
            $previousMonth = $today->copy()->subMonthNoOverflow();
            $candidate = self::clampedCycleDate($previousMonth->year, $previousMonth->month, $anchorDay);
        }

        return $candidate->startOfDay();
    }

    protected static function clampedCycleDate(int $year, int $month, int $day): \Carbon\Carbon
    {
        $daysInMonth = \Carbon\Carbon::createFromDate($year, $month, 1)->daysInMonth;

        return \Carbon\Carbon::createFromDate($year, $month, min($day, $daysInMonth))->startOfDay();
    }

    public function syndics()
    {
        return $this->hasMany(Syndic::class);
    }

    public function owners()
    {
        return $this->hasMany(Owner::class);
    }

    public function serviceProviders()
    {
        return $this->hasMany(ServiceProvider::class);
    }

    public function budgets()
    {
        return $this->hasMany(Budget::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }

    public function meetings()
    {
        return $this->hasMany(Meeting::class);
    }

    public function meetingRequests()
    {
        return $this->hasMany(MeetingRequest::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}