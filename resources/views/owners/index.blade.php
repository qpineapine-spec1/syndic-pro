@extends('layouts.app')

@section('title', 'Gestion des copropriétaires')

@section('content')
    <section class="page-header">
        <div>
            <div class="feature-badge">Gestion des copropriétaires</div>
            <h1 class="page-title" style="margin-top:0.7rem;">Liste des copropriétaires</h1>
        </div>
    </section>

    <section class="card-glass" style="padding:1.5rem; margin-top:1.5rem;">
        @if($owners->isEmpty())
            <p>Aucun copropriétaire trouvé pour votre propriété.</p>
        @else
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="text-align:left; padding:0.8rem; border-bottom:1px solid rgba(0,0,0,0.12);">Nom</th>
                        <th style="text-align:left; padding:0.8rem; border-bottom:1px solid rgba(0,0,0,0.12);">Email</th>
                        <th style="text-align:left; padding:0.8rem; border-bottom:1px solid rgba(0,0,0,0.12);">Statut</th>
                        <th style="text-align:left; padding:0.8rem; border-bottom:1px solid rgba(0,0,0,0.12);">Téléphone</th>
                        <th style="text-align:left; padding:0.8rem; border-bottom:1px solid rgba(0,0,0,0.12);">Lot</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($owners as $owner)
                        <tr>
                            <td style="padding:0.8rem; border-bottom:1px solid rgba(0,0,0,0.08);">{{ $owner->user->name ?? '—' }}</td>
                            <td style="padding:0.8rem; border-bottom:1px solid rgba(0,0,0,0.08);">{{ $owner->user->email ?? '—' }}</td>
                           <td style="padding:0.8rem; border-bottom:1px solid rgba(0,0,0,0.08);">
                                @if($owner->status === 'locataire')
                                    <span class="feature-badge">En location</span>
                                    <div style="margin-top:0.4rem; font-size:0.85rem; color:var(--color-text-muted); line-height:1.5;">
                                        @if($owner->real_owner_name)
                                            <div><strong>Propriétaire réel :</strong> {{ $owner->real_owner_name }}</div>
                                        @endif
                                        @if($owner->tenant)
                                            <div><strong>Locataire :</strong> {{ $owner->tenant->tenant_name ?? '—' }} ({{ $owner->tenant->tenant_phone ?? '—' }})</div>
                                            <div><strong>Contrat :</strong> {{ optional($owner->tenant->contract_start_date)->format('d/m/Y') ?? '—' }} → {{ optional($owner->tenant->contract_end_date)->format('d/m/Y') ?? '—' }}</div>
                                        @endif
                                    </div>
                                @else
                                    Propriétaire
                                @endif
                            </td>
                            <td style="padding:0.8rem; border-bottom:1px solid rgba(0,0,0,0.08);">{{ $owner->telephone ?? '—' }}</td>
                            <td style="padding:0.8rem; border-bottom:1px solid rgba(0,0,0,0.08);">{{ $owner->lot_surface ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
@endsection
