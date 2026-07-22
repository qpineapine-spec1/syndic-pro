@extends('layouts.app')

@section('title', 'Cotisations')

@section('content')
        <section class="page-header">
            <div>
                <div class="feature-badge">Cotisations</div>
                <h1 class="page-title" style="margin-top:0.7rem;">Suivi des cotisations</h1>
            </div>
            <a href="{{ route('dashboard') }}" class="btn-secondary">Retour au tableau de bord</a>
        </section>

        <section class="card-glass" style="padding:1.5rem;">
            @include('partials.flash')

            <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; margin-bottom:1rem; flex-wrap:wrap;">
                <div>
                    <h2 style="margin:0;">État des cotisations</h2>
                    <p style="margin:0.3rem 0 0; color:var(--color-text-muted);">Vue de synthèse pour la copropriété.</p>
                </div>
                @if($canCalculate)
                    <form method="POST" action="{{ route('contributions.calculate') }}">
                        @csrf
                        <input type="hidden" name="property_id" value="{{ auth()->user()->syndic?->property_id }}">
                        <button type="submit" class="btn-primary">Calculer les cotisations</button>
                    </form>
                @else
                    <div class="disabled-feature" style="padding:0.8rem 1rem; border-radius:999px; display:inline-flex; align-items:center; gap:0.6rem;">
                        <span>Calculer les cotisations</span>
                    </div>
                @endif
            </div>

            @if(!$canCalculate)
                <div class="dropdown-empty" style="margin-bottom:1rem; text-align:left; border-left:3px solid var(--color-accent-dark); padding-left:0.9rem;">
                    <strong>Le calcul est actuellement bloqué.</strong>
                    <ul style="margin:0.5rem 0 0 1.2rem; padding:0;">
                        @if(!$incompleteOwners->isEmpty())
                            <li>Des copropriétaires n’ont pas encore de superficie renseignée.</li>
                        @endif
                        @if(!$unactivatedOwners->isEmpty())
                            <li>Des comptes copropriétaire ne sont pas encore activés.</li>
                        @endif
                        @php
                            $budget = \App\Models\Budget::where('property_id', auth()->user()->syndic?->property_id)->where('is_valid', true)->latest('created_at')->first();
                        @endphp
                        @if(!$budget)
                            <li>Aucun budget validé. Rendez-vous sur la page Budget pour valider le budget existant avant de calculer les cotisations.</li>
                            <li><a href="{{ route('budgets.index') }}" class="btn-secondary" style="display:inline-flex; margin-top:0.4rem;">Ouvrir la page Budget</a></li>
                        @endif
                    </ul>
                </div>
            @endif

            @if(!empty($incompleteOwners) && $incompleteOwners->count() > 0)
                <div class="dropdown-empty" style="margin-bottom:1rem; text-align:left;">
                    <strong>{{ $incompleteOwners->count() }} copropriétaire(s) sur {{ $owners->count() }} n'ont pas encore de superficie renseignée.</strong>
                    <ul style="margin:0.5rem 0 0 1.2rem; padding:0;">
                        @foreach($incompleteOwners as $owner)
                            <li>{{ $owner->user?->name ?? 'Copropriétaire' }} — {{ $owner->user?->email ?? 'sans email' }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="expense-section-header">
                <h2 style="font-size:1rem;">Charge surplus</h2>
                <button
                    type="button"
                    id="add-surplus-toggle"
                    class="add-expense-btn"
                    aria-expanded="false"
                    aria-label="Ajouter une charge surplus"
                >+</button>
            </div>

            <div id="surplus-form" class="surplus-form-box" style="display:none;">
                <form action="{{ route('contributions.surplus') }}" method="POST">
                    @csrf
                    <div class="surplus-form-row">
                        <label>
                            Montant à répartir (MAD)
                            <input type="number" step="0.01" min="0.01" name="surplus_amount">
                        </label>
                        <button type="submit" class="btn-primary">Valider</button>
                    </div>
                </form>
            </div>

            <div class="expense-section-header">
                <h2 style="font-size:1rem;">Liste des cotisations</h2>
                <form method="GET" action="{{ route('contributions.index') }}">
                    <select name="status" onchange="this.form.submit()">
                        <option value="" {{ !$statusFilter ? 'selected' : '' }}>Tous les statuts</option>
                        <option value="a_jour" {{ $statusFilter === 'a_jour' ? 'selected' : '' }}>Payé</option>
                        <option value="en_attente" {{ $statusFilter === 'en_attente' ? 'selected' : '' }}>Non payé</option>
                        <option value="en_retard" {{ $statusFilter === 'en_retard' ? 'selected' : '' }}>En retard</option>
                    </select>
                </form>
            </div>

            @if($contributions->isEmpty())
                <div class="dropdown-empty">Aucune cotisation n’a encore été générée pour cette copropriété.</div>
            @else
                <div class="expense-table-wrap">
                    <table class="expense-table expense-table--contributions">
                        <thead>
                            <tr>
                                <th>✓</th>
                                <th>Copropriétaire</th>
                                <th>Étage</th>
                                <th>Cotisation</th>
                                <th>Charges de plus</th>
                                <th>État</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($contributions as $contribution)
                                @php $statusKey = $contribution->computeStatus($cycleStart); @endphp
                                <tr>
                                    <td>
                                        <form action="{{ route('contributions.toggle-paid', $contribution) }}" method="POST">
                                            @csrf
                                            <input
                                                type="checkbox"
                                                name="paid"
                                                value="1"
                                                class="expense-checkbox"
                                                {{ $statusKey === 'a_jour' ? 'checked' : '' }}
                                                onchange="this.form.submit()"
                                                aria-label="Marquer la cotisation de {{ $contribution->owner->user->name ?? 'ce copropriétaire' }} comme payée ce mois-ci"
                                            >
                                        </form>
                                    </td>
                                    <td>{{ $contribution->owner->user->name ?? 'Copropriétaire' }}</td>
                                    <td>{{ $contribution->owner->floor ?? '—' }}</td>
                                    <td>{{ number_format($contribution->montant_mensuel, 2, ',', ' ') }} MAD</td>
                                    <td>{{ $contribution->charges_surplus > 0 ? number_format($contribution->charges_surplus, 2, ',', ' ') . ' MAD' : '—' }}</td>
                                    <td>
                                        @if($statusKey === 'a_jour')
                                            <span class="status-badge status-badge--paid">Payé</span>
                                        @elseif($statusKey === 'en_retard')
                                            <span class="status-badge status-badge--late">En retard</span>
                                        @else
                                            <span class="status-badge status-badge--pending">Non payé</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
@endsection