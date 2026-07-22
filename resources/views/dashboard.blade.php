@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <section class="page-header">
        <div class="page-header__content">
            <div class="feature-badge">Tableau de bord</div>
            <h1 class="page-title">Bienvenue, {{ auth()->user()->name }}</h1>
        </div>
        @if(auth()->user()->role === 'copropriétaire')
            <a href="{{ route('profile.show') }}" class="btn-secondary">Infos personnelles</a>
        @endif
    </section>

    @include('partials.flash')

    <section class="dashboard-grid">
        @if(auth()->user()->role === 'syndic' && $stats)
            <div class="stat-grid">
                <article class="stat-card">
                    <span class="stat-card__icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </span>
                    <span class="stat-card__label">Cotisation collectée</span>
                    <span class="stat-card__value">{{ number_format($stats['contribution_collected'], 0, ',', ' ') }} <small>/ {{ number_format($stats['contribution_expected'], 0, ',', ' ') }} MAD</small></span>
                    <div class="stat-card__bar">
                        <div class="stat-card__bar-fill" style="width: {{ $stats['contribution_expected'] > 0 ? min(100, round($stats['contribution_collected'] / $stats['contribution_expected'] * 100)) : 0 }}%;"></div>
                    </div>
                </article>

                <article class="stat-card">
                    <span class="stat-card__icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 7h16"/><path d="M7 7v10"/><path d="M17 7v10"/><path d="M4 17h16"/></svg>
                    </span>
                    <span class="stat-card__label">Dépenses du mois</span>
                    <span class="stat-card__value">{{ number_format($stats['monthly_expenses'], 0, ',', ' ') }} <small>MAD</small></span>
                </article>

                <article class="stat-card">
                    <span class="stat-card__icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 10a7 7 0 1 1 14 0v8a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/><path d="M10 14h4"/></svg>
                    </span>
                    <span class="stat-card__label">Réclamations en attente</span>
                    <span class="stat-card__value">{{ $stats['pending_complaints'] }}</span>
                </article>

                <article class="stat-card">
                    <span class="stat-card__icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M8 7h8"/><path d="M8 12h8"/><path d="M8 17h5"/><path d="M3 5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                    </span>
                    <span class="stat-card__label">Dernière réunion programmée</span>
                    @if($stats['last_planned_meeting'])
                        <span class="stat-card__value" style="font-size:1.05rem;">{{ $stats['last_planned_meeting']->title }}</span>
                        <span class="stat-card__sub">{{ \Illuminate\Support\Carbon::parse($stats['last_planned_meeting']->meeting_date)->format('d/m/Y à H:i') }}</span>
                    @else
                        <span class="stat-card__sub">Aucune réunion programmée</span>
                    @endif
                </article>
            </div>

            <button type="button" id="stats-toggle" class="stats-toggle-link" aria-expanded="false">
                Statistiques
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
            </button>

            <div id="stats-charts" class="stats-charts">
                @php
                    $months = ['Jan','Fév','Mar','Avr','Mai','Juin','Juil','Août','Sep','Oct','Nov','Déc'];
                    $maxExpense = max(1, max($monthlyExpenseChart));
                    $maxComplaint = max(1, max($monthlyComplaintChart));
                @endphp

                <div class="chart-card">
                    <h3>Dépenses par mois ({{ now()->year }})</h3>
                    <div class="bar-chart">
                        @foreach($monthlyExpenseChart as $i => $value)
                            <div class="bar-chart__col">
                                <div class="bar-chart__bar" style="height: {{ $value > 0 ? max(4, round($value / $maxExpense * 100)) : 2 }}%;" title="{{ $months[$i] }} : {{ number_format($value, 0, ',', ' ') }} MAD"></div>
                                <span class="bar-chart__label">{{ $months[$i] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="chart-card">
                    <h3>Réclamations par mois ({{ now()->year }})</h3>
                    <div class="bar-chart">
                        @foreach($monthlyComplaintChart as $i => $value)
                            <div class="bar-chart__col">
                                <div class="bar-chart__bar bar-chart__bar--accent" style="height: {{ $value > 0 ? max(4, round($value / $maxComplaint * 100)) : 2 }}%;" title="{{ $months[$i] }} : {{ $value }} réclamation(s)"></div>
                                <span class="bar-chart__label">{{ $months[$i] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @if(auth()->user()->role === 'copropriétaire')
            <article class="dashboard-card">
                <div class="feature-badge">Factures impayées</div>
                <h2 style="margin:0.8rem 0 0.3rem;">Notification</h2>
                @if($unpaidInvoices->isEmpty())
                    <p style="margin:0; color:var(--color-text-muted);">Aucune facture impayée en cours.</p>
                @else
                    <ul style="margin:0.4rem 0 0; padding-left:1rem; color:var(--color-text-muted);">
                        @foreach($unpaidInvoices->take(3) as $invoice)
                            <li>{{ $invoice->invoice_number }} — {{ number_format($invoice->amount, 2, ',', ' ') }} €</li>
                        @endforeach
                    </ul>
                @endif
            </article>

            <article class="dashboard-card">
                <div class="feature-badge">Décision du dernier vote</div>
                <h2 style="margin:0.8rem 0 0.3rem;">Dernière décision</h2>
                <p style="margin:0; color:var(--color-text-muted);">{{ $latestClosedVoteDecision ?? 'Aucune décision disponible pour le moment.' }}</p>
            </article>

            <article class="dashboard-card">
                <div class="feature-badge">Réunion</div>
                <h2 style="margin:0.8rem 0 0.3rem;">Dernière réunion</h2>
                @if($lastMeeting)
                    <p style="margin:0; font-weight:700;">{{ $lastMeeting->title }}</p>
                    <small>{{ \Illuminate\Support\Carbon::parse($lastMeeting->meeting_date)->format('d/m/Y à H:i') }}</small>
                    @if($meetingReportRoute)
                        <p style="margin:0.4rem 0 0;"><a href="{{ $meetingReportRoute }}" class="btn-secondary">Voir le compte rendu</a></p>
                    @endif
                @else
                    <p style="margin:0; color:var(--color-text-muted);">Aucune réunion enregistrée pour le moment.</p>
                @endif
            </article>
        @endif

        @unless(auth()->user()->role === 'syndic')
            <article class="dashboard-card">
                <div class="feature-badge">Réunions</div>
                <h2 style="margin:0.8rem 0 0.3rem;">Dernière réunion</h2>
                @if($lastMeeting)
                    <p style="margin:0; font-weight:700;">{{ $lastMeeting->title }}</p>
                    <small>{{ \Illuminate\Support\Carbon::parse($lastMeeting->meeting_date)->format('d/m/Y à H:i') }}</small>
                @else
                    <p style="margin:0; color:var(--color-text-muted);">Aucune réunion enregistrée pour le moment.</p>
                @endif
            </article>
        @endunless
    </section>
@endsection