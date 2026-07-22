@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Historique des actions</h1>

    <form method="get" class="mb-4">
        <div>
            <label for="type">Type d'action</label>
            <input id="type" name="type" type="text" value="{{ old('type', $filterType) }}">
        </div>
        <div>
            <label for="user_id">Utilisateur</label>
            <input id="user_id" name="user_id" type="text" value="{{ old('user_id', $filterUserId) }}">
        </div>
        <button type="submit">Filtrer</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Action</th>
                <th>Utilisateur</th>
                <th>Détails</th>
            </tr>
        </thead>
        <tbody>
            @forelse($activities as $activity)
                <tr>
                    <td>{{ $activity->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $activity->description }}</td>
                    <td>{{ $activity->causer_type ? class_basename($activity->causer_type) . ' #' . $activity->causer_id : 'Système' }}</td>
                    <td>{{ json_encode($activity->properties->toArray()) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">Aucun historique trouvé.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $activities->withQueryString()->links() }}
</div>
@endsection
