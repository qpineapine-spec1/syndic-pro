@extends('layouts.app')

@section('content')
<div class="card-glass" style="padding:2rem;">
    <h2>Prévisualisation de l'import</h2>

    <h3>Copropriétaires</h3>
    <table>
        <thead><tr><th>Nom</th><th>Prenom</th><th>Email</th><th>Téléphone</th></tr></thead>
        <tbody>
        @foreach(($data['owners'] ?? []) as $o)
            <tr>
                <td>{{ $o['last_name'] ?? '' }}</td>
                <td>{{ $o['first_name'] ?? '' }}</td>
                <td>{{ $o['email'] ?? '' }}</td>
                <td>{{ $o['phone'] ?? '' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h3>Prestataires</h3>
    <table>
        <thead><tr><th>Nom</th><th>Debut</th><th>Fin</th><th>Mensuel</th></tr></thead>
        <tbody>
        @foreach(($data['service_providers'] ?? []) as $s)
            <tr>
                <td>{{ $s['name'] ?? '' }}</td>
                <td>{{ $s['contract_start'] ?? '' }}</td>
                <td>{{ $s['contract_end'] ?? '' }}</td>
                <td>{{ $s['monthly_amount'] ?? '' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <form action="{{ route('import.confirm') }}" method="post">
        @csrf
        <button class="btn-primary">Confirmer et Importer</button>
        <a href="{{ route('import.upload') }}" class="btn-secondary">Annuler</a>
    </form>
</div>
@endsection
