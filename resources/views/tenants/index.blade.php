@extends('layouts.app')

@section('title', 'Locataire / bureau')

@section('content')
    <section class="page-header">
        <div>
            <div class="feature-badge">Locataire &amp; bureau</div>
            <h1 class="page-title">
                @if(auth()->user()->role === 'syndic')
                    Gestion des locataires
                @else
                    État de votre bureau
                @endif
            </h1>
        </div>
        @if(auth()->user()->role !== 'syndic')
            <a href="{{ route('profile.show') }}" class="btn-secondary">Retour au profil</a>
        @endif
    </section>

    @if(session('status'))
        <div class="dashboard-card" style="margin-top:1rem;">{{ session('status') }}</div>
    @endif

    @if(auth()->user()->role === 'syndic')
        {{-- Tableau de tous les locataires de la copropriété --}}
        <section class="card-glass" style="padding:1.5rem; margin-top:1.5rem; overflow-x:auto;">
            @if($tenants->isEmpty())
                <div class="dropdown-empty">Aucun locataire disponible pour le moment.</div>
            @else
                <table class="table" style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th style="text-align:left; padding:0.5rem;">Bureau</th>
                            <th style="text-align:left; padding:0.5rem;">Propriétaire</th>
                            <th style="text-align:left; padding:0.5rem;">Locataire</th>
                            <th style="text-align:left; padding:0.5rem;">Téléphone</th>
                            <th style="text-align:left; padding:0.5rem;">Début contrat</th>
                            <th style="text-align:left; padding:0.5rem;">Fin contrat</th>
                            <th style="text-align:left; padding:0.5rem;">État</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tenants as $t)
                            <tr>
                                <td style="padding:0.5rem;">{{ $t->owner->office_number ?? '—' }}</td>
                                <td style="padding:0.5rem;">{{ $t->owner->real_owner_name ?? '—' }}</td>
                                <td style="padding:0.5rem;">{{ $t->tenant_name ?? '—' }}</td>
                                <td style="padding:0.5rem;">{{ $t->tenant_phone ?? '—' }}</td>
                                <td style="padding:0.5rem;">{{ optional($t->contract_start_date)->format('d/m/Y') ?? '—' }}</td>
                                <td style="padding:0.5rem;">{{ optional($t->contract_end_date)->format('d/m/Y') ?? '—' }}</td>
                                <td style="padding:0.5rem;">
                                    <span class="feature-badge">{{ $t->is_active ? 'En location' : 'Contrat terminé' }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    @else
        {{-- Espace bureau : état du contrat locatif du bureau connecté --}}
        <section class="card-glass" style="padding:1.5rem; margin-top:1.5rem;">
            @if(isset($tenant) && $tenant)
                <div style="display:grid; gap:0.7rem;">
                    <div><strong>Nom du locataire :</strong> {{ $tenant->tenant_name ?? '—' }}</div>
                    <div><strong>Téléphone du locataire :</strong> {{ $tenant->tenant_phone ?? '—' }}</div>
                    <div><strong>Date de début de contrat :</strong> {{ optional($tenant->contract_start_date)->format('d/m/Y') ?? '—' }}</div>
                    <div><strong>Date de fin de contrat :</strong> {{ optional($tenant->contract_end_date)->format('d/m/Y') ?? '—' }}</div>
                    <div><strong>État du bureau :</strong> {{ $owner->status === 'locataire' ? 'En location' : 'Propriétaire' }}</div>
                </div>
            @else
                <div class="dropdown-empty" style="margin-bottom:1rem;">Aucun contrat locatif n'est actuellement enregistré pour ce bureau.</div>

                <p style="color:var(--color-text-muted); margin-bottom:1rem;">Si vous louez votre bureau, vous pouvez enregistrer votre locataire ci-dessous. Le bureau passera automatiquement à l'état « En location » et apparaîtra dans le tableau du syndic.</p>

                <form action="{{ route('tenants.store') }}" method="POST" style="display:grid; gap:0.9rem;">
                    @csrf
                    <div style="display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:0.9rem;">
                        <div style="display:grid; gap:0.35rem;">
                            <label for="tenant_name">Nom et prénom du locataire</label>
                            <input id="tenant_name" type="text" name="tenant_name" required class="form-control">
                        </div>
                        <div style="display:grid; gap:0.35rem;">
                            <label for="tenant_phone">Numéro de téléphone</label>
                            <input id="tenant_phone" type="text" name="tenant_phone" required class="form-control">
                        </div>
                        <div style="display:grid; gap:0.35rem;">
                            <label for="contract_start_date">Date de début de contrat</label>
                            <input id="contract_start_date" type="date" name="contract_start_date" required class="form-control">
                        </div>
                        <div style="display:grid; gap:0.35rem;">
                            <label for="contract_end_date">Date de fin de contrat</label>
                            <input id="contract_end_date" type="date" name="contract_end_date" required class="form-control">
                        </div>
                    </div>
                    <div>
                        <button type="submit" class="btn-primary">Ajouter le locataire</button>
                    </div>
                </form>
            @endif
        </section>
    @endif
@endsection