@extends('layouts.app')

@section('title', 'Règlement de copropriété')

@section('content')
<div class="card-glass" style="padding:2rem;">
    <h2>Règlement de copropriété</h2>

    @include('partials.flash')

    @if($property->reglement_fichier)
        <div class="alert alert-warning" style="margin-bottom:1rem;">
            <strong>Un règlement est déjà en ligne.</strong> Le téléverser à nouveau remplacera le fichier actuel, visible par tous les copropriétaires et depuis l'accueil public.
        </div>
        <p style="margin-bottom:1.2rem;">
            <a href="{{ route('properties.reglement.download', $property) }}" class="btn-secondary">Voir le fichier actuel</a>
        </p>
    @else
        <p style="margin-bottom:1.2rem; color:var(--color-muted, #6b7280);">
            Aucun règlement n'a encore été téléversé. Une fois envoyé, il sera visible et téléchargeable par tous les copropriétaires ainsi que depuis la page d'accueil publique.
        </p>
    @endif

    <form action="{{ route('properties.reglement.upload', $property) }}" method="post" enctype="multipart/form-data">
        @csrf
        <div>
            <label for="reglement">Fichier du règlement</label>
            <input type="file" name="reglement" id="reglement" required />
        </div>
        @error('reglement')
            <div class="alert alert-danger" style="margin-top:1rem;">{{ $message }}</div>
        @enderror

        <div style="margin-top:1rem;">
            <button class="btn-primary">Téléverser</button>
        </div>
    </form>
</div>
@endsection
