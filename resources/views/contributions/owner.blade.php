@extends('layouts.app')

@section('title', 'Ma cotisation')

@section('content')
    <section class="page-header">
        <div>
            <div class="feature-badge">Cotisation</div>
            <h1 class="page-title">Ma cotisation</h1>
        </div>
        <a href="{{ route('dashboard') }}" class="btn-secondary">Retour</a>
    </section>

    <section class="card-glass" style="padding:1.5rem; margin-top:1.5rem;">
        @if($contribution)
            <div style="display:grid; gap:0.8rem; margin-bottom:1.5rem;">
                <div><strong>Contribution attribuée :</strong> {{ $contribution->budget?->year ?? '—' }}</div>
                <div><strong>Tantième :</strong> {{ number_format($contribution->tantieme, 2, ',', ' ') }} %</div>
                <div><strong>Montant annuel :</strong> {{ number_format($contribution->montant_annuel, 2, ',', ' ') }} €</div>
                <div><strong>Montant mensuel :</strong> {{ number_format($contribution->montant_mensuel, 2, ',', ' ') }} €</div>
            </div>
        @else
            <div class="dropdown-empty">Aucune contribution n’a encore été attribuée à votre bureau.</div>
        @endif

        <h2 style="margin-top:0;">Cotisation non payée</h2>
        @if(!$unpaidContribution)
            <div class="dropdown-empty">Aucune cotisation impayée.</div>
        @else
            <div class="expense-table-wrap">
                <table class="expense-table">
                    <thead>
                        <tr>
                            <th>Mois</th>
                            <th>Montant</th>
                            <th>Date d'échéance</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $cycleStart ? ucfirst($cycleStart->translatedFormat('F Y')) : '—' }}</td>
                            <td>{{ number_format($unpaidContribution->montant_mensuel, 2, ',', ' ') }} MAD</td>
                            <td>{{ $dueDate?->format('d/m/Y') ?? '—' }}</td>
                            <td>
                                <span class="status-badge {{ $contributionStatus === 'en_retard' ? 'status-badge--late' : 'status-badge--pending' }}">
                                    {{ $contributionStatus === 'en_retard' ? 'En retard' : 'Non payé' }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif

        <h2 style="margin-top:1.5rem;">Historique de mes cotisations payées</h2>
        @if($paymentHistory->isEmpty())
            <div class="dropdown-empty">Aucun paiement de cotisation enregistré pour le moment.</div>
        @else
            <div class="expense-table-wrap">
                <table class="expense-table">
                    <thead>
                        <tr>
                            <th>Mois</th>
                            <th>Date de paiement</th>
                            <th>Montant</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paymentHistory as $payment)
                            <tr>
                                <td>{{ ucfirst($payment->paid_at->translatedFormat('F Y')) }}</td>
                                <td>{{ $payment->paid_at->format('d/m/Y') }}</td>
                                <td>{{ number_format($payment->amount, 2, ',', ' ') }} MAD</td>
                                <td><span class="status-badge status-badge--paid">Payé</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    </section>
@endsection