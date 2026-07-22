@extends('layouts.app')

@section('title', 'Dépenses')

@section('content')
        <section class="page-header">
            <div>
                <div class="feature-badge">Dépenses</div>
                <h1 class="page-title">Suivi des dépenses</h1>
            </div>
            <a href="{{ route('dashboard') }}" class="btn-secondary">Retour</a>
        </section>

        @if(session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert-danger">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <section class="card-glass" style="padding:1.5rem;">

            {{-- ================= DÉPENSES FIXES ================= --}}
            <div class="expense-section-header">
                <h2>Dépenses Fixes</h2>
                @if($cycleStart)
                    <span class="feature-badge" style="margin-top:0;">Cycle en cours depuis le {{ $cycleStart->format('d/m/Y') }}</span>
                @endif
            </div>

            <div class="expense-table-wrap" style="margin-bottom:1rem;">
                <table class="expense-table expense-table--fixed">
                    <thead>
                        <tr>
                            <th>✓</th>
                            <th>Nom de la dépense</th>
                            <th>Montant</th>
                            <th>Facture</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($fixedExpenses as $expense)
                            <tr>
                                <td>
                                    @if(auth()->user()->role === 'syndic')
                                        <form action="{{ route('expenses.toggle-paid', $expense) }}" method="POST">
                                            @csrf
                                            <input
                                                type="checkbox"
                                                name="paid"
                                                value="1"
                                                class="expense-checkbox"
                                                {{ $expense->isPaidThisCycle($cycleStart) ? 'checked' : '' }}
                                                onchange="this.form.submit()"
                                                aria-label="Marquer {{ $expense->label }} comme payée ce mois-ci"
                                            >
                                        </form>
                                    @else
                                        <input type="checkbox" class="expense-checkbox" disabled {{ $expense->isPaidThisCycle($cycleStart) ? 'checked' : '' }}>
                                    @endif
                                </td>
                                <td>{{ $expense->label }}</td>
                                <td>{{ number_format($expense->amount, 2, ',', ' ') }} MAD</td>
                                <td>
                                    @if($expense->fichier_facture)
                                        <a href="{{ route('expenses.download-facture', $expense) }}" class="facture-link">Voir facture</a>
                                    @else
                                        <span style="color: var(--color-text-muted);">Aucune facture</span>
                                    @endif
                                </td>
                                <td class="expense-action-cell">
                                    @if(auth()->user()->role === 'syndic')
                                        @if($expense->fichier_facture)
                                            <button type="button" class="btn-secondary btn-sm" disabled>Uploadé</button>
                                        @else
                                            <form action="{{ route('expenses.upload', $expense) }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                <button type="button" class="btn-secondary btn-sm" onclick="this.nextElementSibling.click()">Uploader</button>
                                                <input type="file" name="fichier_facture" style="display:none" onchange="this.form.submit()" required>
                                            </form>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr class="is-empty-row">
                                <td colspan="5">Aucune dépense fixe enregistrée pour cette copropriété.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ================= DÉPENSES VARIABLES ================= --}}
            <div class="expense-section-header">
                <h2>Dépenses Variables</h2>
                @if(auth()->user()->role === 'syndic')
                    <button type="button" class="add-expense-btn" id="add-expense-toggle" aria-expanded="false" aria-label="Ajouter une charge variable" {{ $variableLimitReached ? 'disabled' : '' }}>+</button>
                @endif
            </div>

            @if($variableCeiling !== null)
                <div class="variable-budget-summary">
                    <span>Budget fixé par l'assemblée générale : <strong>{{ number_format($variableCeiling, 2, ',', ' ') }} MAD</strong></span>
                    <span>Déjà utilisé : <strong>{{ number_format($variableSpent, 2, ',', ' ') }} MAD</strong></span>
                    <span>Restant : <strong class="{{ $variableRemaining <= $variableCeiling * 0.1 ? 'is-low' : '' }}">{{ number_format($variableRemaining, 2, ',', ' ') }} MAD</strong></span>
                </div>
            @endif

            @if($variableLimitReached)
                <div class="alert-warning">
                    Vous avez atteint le montant fixé pour les charges variables. Vous ne pouvez plus créer de nouvelle dépense variable pour ce cycle.
                </div>
            @endif

            @if(auth()->user()->role === 'syndic' && !$variableLimitReached)
                <div class="variable-expense-form-inline" id="variable-expense-form">
                    <form action="{{ route('expenses.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-row">
                            <label>
                                Catégorie
                                <select name="categorie" id="variable-category" required>
                                    <option value="" selected>Choisir votre catégorie</option>
                                    @foreach($categories as $value => $labelText)
                                        <option value="{{ $value }}">{{ $labelText }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>

                        <div id="variable-amount-step" class="reveal-step">
                            <label>
                                Montant (MAD)
                                <input type="number" step="0.01" min="0.01" name="amount" id="variable-amount" @if($variableRemaining !== null) max="{{ $variableRemaining }}" @endif>
                            </label>
                            <label>
                                Description (optionnel)
                                <input type="text" name="description" maxlength="255" placeholder="Détail de la dépense">
                            </label>
                        </div>

                        <div id="variable-upload-step" class="reveal-step">
                            <label>
                                Facture (optionnel)
                                <input type="file" name="fichier_facture">
                            </label>
                            <button type="submit" class="btn-primary">Valider</button>
                        </div>
                    </form>
                </div>
            @endif

            <div class="expense-table-wrap">
                <table class="expense-table expense-table--variable">
                    <thead>
                        <tr>
                            <th>Catégorie</th>
                            <th>Montant</th>
                            <th>Description</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($variableExpenses as $expense)
                            <tr>
                                <td>{{ $expense->categorie ?? '—' }}</td>
                                <td>{{ number_format($expense->amount, 2, ',', ' ') }} MAD</td>
                                <td>{{ $expense->label ?: '—' }}</td>
                                <td class="expense-action-cell">
                                    @if($expense->fichier_facture)
                                        <a href="{{ route('expenses.download-facture', $expense) }}" class="facture-link">Voir facture</a>
                                    @else
                                        <span style="color: var(--color-text-muted);">Pas de facture uploadée</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr class="is-empty-row">
                                <td colspan="4">Aucune dépense variable enregistrée depuis le dernier import de l'assemblée générale.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
@endsection