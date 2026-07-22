@extends('layouts.app')

@section('title', 'Créer un budget')

@section('content')
        <section class="page-header">
            <div>
                <div class="feature-badge">Nouveau budget</div>
                <h1 class="page-title">Créer un budget</h1>
            </div>
            <a href="{{ route('budgets.index') }}" class="btn-secondary">Retour</a>
        </section>

        <section class="card-glass" style="padding:1.5rem; max-width:720px;">
            <form method="POST" action="{{ route('budgets.store') }}">
                @csrf
                <div style="display:grid; gap:0.8rem;">
                    <label>Année
                        <input type="number" name="year" class="form-input" required>
                    </label>

                    <label>Charges fixes totales (€)
                        <input type="number" step="0.01" name="fixed_charges_total" class="form-input" required>
                    </label>

                    <label>Charges variables totales (€)
                        <input type="number" step="0.01" name="variable_charges_total" class="form-input" required>
                    </label>

                    <div style="display:flex; gap:0.8rem;">
                        <button class="btn-primary" type="submit">Enregistrer</button>
                        <a href="{{ route('budgets.index') }}" class="btn-secondary">Annuler</a>
                    </div>
                </div>
            </form>
        </section>
@endsection