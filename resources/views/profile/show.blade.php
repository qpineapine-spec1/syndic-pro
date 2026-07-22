@extends('layouts.app')

@section('title', 'Mes informations personnelles')

@section('content')
        <section class="page-header">
            <div>
                <div class="feature-badge">Profil copropriétaire</div>
                <h1 class="page-title" style="margin-top:0.7rem;">Mes informations personnelles</h1>
            </div>
        </section>

        @include('partials.flash')

        <section class="card-glass" style="max-width:600px; margin:2rem auto; padding:2rem;">
            <form method="POST" action="{{ route('profile.update') }}" class="form-grid">
                @csrf
                @method('PATCH')

                <div class="form-field">
                    <label for="email">Email (non modifiable)</label>
                    <input id="email" type="email" name="email" value="{{ $user->email }}" readonly>
                </div>

                <div class="form-field">
                    <label for="name">Nom complet</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required>
                    @error('name') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-field">
                    <label for="lot_surface">Surface du lot (m²)</label>
                    <input id="lot_surface" type="number" name="lot_surface" value="{{ old('lot_surface', $owner->lot_surface) }}" step="0.01" required>
                    @error('lot_surface') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-field">
                    <label for="surface_confirmation">Confirmation de surface (m²)</label>
                    <input id="surface_confirmation" type="number" name="surface_confirmation" value="{{ old('surface_confirmation', $owner->surface_confirmation) }}" step="0.01" required>
                    @error('surface_confirmation') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-field">
                    <label for="has_mezzanine">Mezzanine ?</label>
                    <select id="has_mezzanine" name="has_mezzanine" required>
                        <option value="0" {{ old('has_mezzanine', $owner->has_mezzanine ? '1' : '0') === '0' ? 'selected' : '' }}>Non</option>
                        <option value="1" {{ old('has_mezzanine', $owner->has_mezzanine ? '1' : '0') === '1' ? 'selected' : '' }}>Oui</option>
                    </select>
                    @error('has_mezzanine') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-field" id="mezzanine_surface_wrapper" style="display:{{ old('has_mezzanine', $owner->has_mezzanine ? '1' : '0') === '1' ? 'block' : 'none' }};">
                    <label for="mezzanine_surface">Surface mezzanine (m²)</label>
                    <input id="mezzanine_surface" type="number" name="mezzanine_surface" value="{{ old('mezzanine_surface', $owner->mezzanine_surface) }}" step="0.01">
                    @error('mezzanine_surface') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-field">
                    <label for="office_number">Numéro de bureau</label>
                    <input id="office_number" type="text" name="office_number" value="{{ old('office_number', $owner->office_number) }}">
                    @error('office_number') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-field">
                    <label for="floor">Étage</label>
                    <input id="floor" type="number" name="floor" value="{{ old('floor', $owner->floor) }}">
                    @error('floor') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-field">
                    <label for="telephone">Téléphone</label>
                    <input id="telephone" type="text" name="telephone" value="{{ old('telephone', $owner->telephone) }}" maxlength="20">
                    @error('telephone') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div style="display:flex; gap:1rem; margin-top:1.5rem;">
                    <button type="submit" class="btn-primary">Enregistrer les modifications</button>
                    <a href="{{ route('dashboard') }}" class="btn-secondary" style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center;">Retour au dashboard</a>
                </div>
            </form>

            <script>
                document.getElementById('has_mezzanine').addEventListener('change', function() {
                    const wrapper = document.getElementById('mezzanine_surface_wrapper');
                    const input = document.getElementById('mezzanine_surface');
                    if (this.value === '1') {
                        wrapper.style.display = 'block';
                    } else {
                        wrapper.style.display = 'none';
                        input.value = '';
                    }
                });
            </script>
        </section>
@endsection
