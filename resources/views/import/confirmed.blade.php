@extends('layouts.app')

@section('content')
<div class="card-glass" style="padding:2rem;">
    <h2>Import terminé</h2>

    <h3>Résultats</h3>
    <ul>
        <li>Copropriétaires créés (invitations) : {{ count($results['owners_created'] ?? []) }}</li>
        <li>Copropriétaires ignorés (existants) : {{ count($results['owners_skipped'] ?? []) }}</li>
        <li>Prestataires : {{ count($results['service_providers'] ?? []) }}</li>
        <li>Budget : {{ $results['budget'] ?? 'aucun' }}</li>
        <li>Lignes de charges importées : {{ count($results['expenses'] ?? []) }}</li>
    </ul>
    <div style="margin-top:1rem;">
        <a href="{{ route('dashboard') }}" class="btn-secondary">Retour au tableau de bord</a>
    </div>
</div>
@endsection
