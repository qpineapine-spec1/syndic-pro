<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Complaint;
use App\Models\Contribution;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Meeting;
use App\Models\Property;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $lastMeeting = null;
        $unpaidInvoices = collect();
        $latestClosedVote = null;
        $latestClosedVoteDecision = null;
        $meetingReportRoute = null;

        // ----- Espace Syndic : les 4 blocs + les 2 graphiques -----
        $stats = null;
        $monthlyExpenseChart = null;
        $monthlyComplaintChart = null;

        if ($user->role === 'syndic') {
            $property = $user->syndic?->property;
            if ($property) {
                $lastMeeting = Meeting::where('property_id', $property->id)
                    ->orderByDesc('meeting_date')
                    ->first();

                $stats = $this->buildSyndicStats($property);
                $monthlyExpenseChart = $this->monthlyExpenseTotals($property);
                $monthlyComplaintChart = $this->monthlyComplaintCounts($property);
            }
        } elseif ($user->role === 'copropriétaire') {
            $owner = $user->owner;
            if ($owner) {
                $lastMeeting = Meeting::where('property_id', $owner->property_id)
                    ->where('meeting_date', '<=', now())
                    ->orderByDesc('meeting_date')
                    ->first();

                $unpaidInvoices = Invoice::where('owner_id', $owner->id)
                    ->where('status', '!=', 'paid')
                    ->orderBy('due_date')
                    ->get();

                $latestClosedVote = Vote::whereHas('meeting', function ($query) use ($owner) {
                    $query->where('property_id', $owner->property_id);
                })
                    ->where('status', 'closed')
                    ->latest('ends_at')
                    ->first();

                $meetingReportRoute = $lastMeeting && $lastMeeting->compte_rendu
                    ? route('meetings.report.download', $lastMeeting)
                    : null;

                if ($latestClosedVote) {
                    $winner = $latestClosedVote->voteChoices()->withCount('voteParticipations')->orderByDesc('vote_participations_count')->first();
                    $latestClosedVoteDecision = $winner ? $winner->label . ' (' . $winner->vote_participations_count . ' voix)' : 'Aucune option enregistrée';
                }
            }
        }

        return view('dashboard', compact(
            'lastMeeting',
            'unpaidInvoices',
            'latestClosedVote',
            'latestClosedVoteDecision',
            'meetingReportRoute',
            'stats',
            'monthlyExpenseChart',
            'monthlyComplaintChart'
        ));
    }

    /**
     * Construit les 4 blocs de statistiques de l'espace syndic.
     */
    protected function buildSyndicStats(Property $property): array
    {
        $cycleStart = $property->currentBillingCycleStart();

        // --- Bloc 1 : Cotisation collectée ce mois-ci ---
        $latestBudget = Budget::where('property_id', $property->id)
            ->where('is_valid', true)
            ->latest('created_at')
            ->first();

        $contributions = $latestBudget
            ? Contribution::where('budget_id', $latestBudget->id)->get()
            : collect();

        $expectedMonthly = (float) $contributions->sum('montant_mensuel');

        $collectedMonthly = $cycleStart
            ? (float) $contributions->filter(
                fn ($contribution) => $contribution->paid_at && $contribution->paid_at->gte($cycleStart)
            )->sum('montant_mensuel')
            : 0.0;

        // --- Bloc 2 : Dépenses du mois (charges fixes payées + charges variables du cycle) ---
        $fixedSpent = 0.0;
        $variableSpent = 0.0;
        if ($cycleStart) {
            $fixedSpent = (float) Expense::where('property_id', $property->id)
                ->where('type', 'fixe')
                ->whereNotNull('paid_at')
                ->where('paid_at', '>=', $cycleStart)
                ->sum('amount');

            $variableSpent = (float) Expense::where('property_id', $property->id)
                ->where('type', 'variable')
                ->where('status', '!=', 'imported')
                ->where('expense_date', '>=', $cycleStart)
                ->sum('amount');
        }
        $monthlyExpenses = $fixedSpent + $variableSpent;

        // --- Bloc 3 : Réclamations en attente (jamais consultées par le syndic) ---
        $pendingComplaints = Complaint::where('property_id', $property->id)
            ->where('status', 'nouvelle')
            ->count();

        // --- Bloc 4 : Dernière réunion programmée ---
        $lastPlannedMeeting = Meeting::where('property_id', $property->id)
            ->orderByDesc('meeting_date')
            ->first();

        return [
            'cycle_start' => $cycleStart,
            'contribution_collected' => $collectedMonthly,
            'contribution_expected' => $expectedMonthly,
            'monthly_expenses' => $monthlyExpenses,
            'pending_complaints' => $pendingComplaints,
            'last_planned_meeting' => $lastPlannedMeeting,
        ];
    }

    /**
     * Total des dépenses (fixes + variables) par mois pour l'année en cours.
     * Retourne un tableau de 12 valeurs (index 0 = janvier ... 11 = décembre).
     */
    protected function monthlyExpenseTotals(Property $property): array
    {
        $year = now()->year;
        $totals = array_fill(0, 12, 0.0);

        // Charges fixes : on se base sur la date à laquelle elles ont été cochées "payées".
        Expense::where('property_id', $property->id)
            ->where('type', 'fixe')
            ->whereNotNull('paid_at')
            ->whereYear('paid_at', $year)
            ->get(['amount', 'paid_at'])
            ->each(function ($expense) use (&$totals) {
                $month = Carbon::parse($expense->paid_at)->month - 1;
                $totals[$month] += (float) $expense->amount;
            });

        // Charges variables : on se base sur la date de la dépense.
        Expense::where('property_id', $property->id)
            ->where('type', 'variable')
            ->where('status', '!=', 'imported')
            ->whereYear('expense_date', $year)
            ->get(['amount', 'expense_date'])
            ->each(function ($expense) use (&$totals) {
                $month = Carbon::parse($expense->expense_date)->month - 1;
                $totals[$month] += (float) $expense->amount;
            });

        return $totals;
    }

    /**
     * Nombre de réclamations créées par mois pour l'année en cours.
     */
    protected function monthlyComplaintCounts(Property $property): array
    {
        $year = now()->year;
        $counts = array_fill(0, 12, 0);

        Complaint::where('property_id', $property->id)
            ->whereYear('created_at', $year)
            ->get(['created_at'])
            ->each(function ($complaint) use (&$counts) {
                $month = Carbon::parse($complaint->created_at)->month - 1;
                $counts[$month]++;
            });

        return $counts;
    }
}