@extends('layouts.app')

@section('title', 'Dépenses')

@section('content')
        <section class="page-header">
            <div>
                <div class="feature-badge">Dépenses</div>
                <h1 class="page-title">Historique des dépenses</h1>
            </div>
            <a href="{{ route('dashboard') }}" class="btn-secondary">Retour</a>
        </section>

        @if(session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        <section class="card-glass" style="padding:1.5rem;">
            <div class="expense-table-wrap">
                <table class="expense-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Nom de la dépense</th>
                            <th>Montant</th>
                            <th>Facture</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $allExpenses = $fixedExpenses->concat($variableExpenses)->sortByDesc(fn ($e) => $e->paid_at ?? $e->expense_date); @endphp
                        @forelse($allExpenses as $expense)
                            <tr>
                                <td>{{ ($expense->paid_at ?? $expense->expense_date)?->format('d/m/Y') }}</td>
                                <td>{{ $expense->label ?: ($expense->categorie ?? '—') }}</td>
                                <td>{{ number_format($expense->amount, 2, ',', ' ') }} MAD</td>
                                <td>
                                    @if($expense->fichier_facture)
                                        <a href="{{ route('expenses.download-facture', $expense) }}" class="facture-link">Voir facture</a>
                                    @else
                                        <span style="color: var(--color-text-muted);">Aucune facture</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr class="is-empty-row">
                                <td colspan="4">Aucune dépense n'a été effectuée jusqu'à maintenant par le syndic.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
@endsection