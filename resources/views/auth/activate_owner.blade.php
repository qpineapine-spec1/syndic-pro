<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activation du compte copropriétaire</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="page-fade-in">
    <main class="auth-shell">
        <section class="form-glass auth-card">
            <div style="display:flex; justify-content:center; margin-bottom:1.5rem;">
                <div class="feature-badge">Activation copropriétaire</div>
            </div>
            <h1 style="margin:0; text-align:center; font-size:2rem;">Complétez votre activation</h1>
            <p style="margin:0.5rem 0 1.75rem; text-align:center; color:var(--color-text-muted);">Renseignez vos informations et définissez un mot de passe.</p>
            @include('partials.flash')
            <form method="POST" action="{{ route('activate.store', ['token' => $token]) }}" class="form-grid">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="form-field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ $email }}" readonly>
                    @error('email') <div class="form-error error-email">{{ $message }}</div> @enderror
                </div>
                <div class="form-field">
                    <label for="name">Nom complet</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required>
                    @error('name') <div class="form-error error-name">{{ $message }}</div> @enderror
                </div>
                <div class="form-field">
                    <label for="password">Nouveau mot de passe</label>
                    <input id="password" type="password" name="password" required>
                    @error('password') <div class="form-error error-password">{{ $message }}</div> @enderror
                </div>
                <div class="form-field">
                    <label for="password_confirmation">Confirmer le mot de passe</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required>
                </div>
                <div class="form-field">
                    <label for="status">Occupation du lot</label>
                    <select id="status" name="status" required>
                        <option value="" {{ old('status') === '' ? 'selected' : '' }}>Sélectionner</option>
                        <option value="proprietaire" {{ old('status') === 'proprietaire' ? 'selected' : '' }}>Propriétaire</option>
                        <option value="locataire" {{ old('status') === 'locataire' ? 'selected' : '' }}>Locataire</option>
                    </select>
                    <small style="color: var(--color-text-muted); display: block; margin-top: 0.3rem;">Indiquez si vous êtes propriétaire occupant ou locataire du lot.</small>
                    @error('status') <div class="form-error error-status">{{ $message }}</div> @enderror
                </div>
                <div class="form-field">
                    <label for="lot_surface">Surface du lot (m²)</label>
                    <input id="lot_surface" type="number" name="lot_surface" value="{{ old('lot_surface') }}" step="0.01" required>
                    @error('lot_surface') <div class="form-error error-lot_surface">{{ $message }}</div> @enderror
                </div>
                <div class="form-field">
                    <label for="surface_confirmation">Confirmation de surface (m²)</label>
                    <input id="surface_confirmation" type="number" name="surface_confirmation" value="{{ old('surface_confirmation') }}" step="0.01" required>
                    @error('surface_confirmation') <div class="form-error error-surface_confirmation">{{ $message }}</div> @enderror
                </div>
                <div class="form-field">
                    <label for="has_mezzanine">Mezzanine ?</label>
                    <select id="has_mezzanine" name="has_mezzanine" required>
                        <option value="0" {{ old('has_mezzanine') === '0' ? 'selected' : '' }}>Non</option>
                        <option value="1" {{ old('has_mezzanine') === '1' ? 'selected' : '' }}>Oui</option>
                    </select>
                    @error('has_mezzanine') <div class="form-error error-has_mezzanine">{{ $message }}</div> @enderror
                </div>
                <div class="form-field" id="mezzanine_surface_wrapper" style="display:{{ old('has_mezzanine') === '1' ? 'block' : 'none' }};">
                    <label for="mezzanine_surface">Surface mezzanine (m²)</label>
                    <input id="mezzanine_surface" type="number" name="mezzanine_surface" value="{{ old('mezzanine_surface') }}" step="0.01">
                    @error('mezzanine_surface') <div class="form-error error-mezzanine_surface">{{ $message }}</div> @enderror
                </div>
                <div class="form-field">
                    <label for="telephone">Numéro de téléphone</label>
                    <input id="telephone" type="text" name="telephone" value="{{ old('telephone') }}">
                    @error('telephone') <div class="form-error error-telephone">{{ $message }}</div> @enderror
                </div>
                <div class="form-field" id="real_owner_name_wrapper" style="display:{{ old('status') === 'locataire' ? 'block' : 'none' }};">
                    <label for="real_owner_name">Nom et prénom du propriétaire réel du bureau</label>
                    <input id="real_owner_name" type="text" name="real_owner_name" value="{{ old('real_owner_name') }}">
                    <small style="color: var(--color-text-muted); display: block; margin-top: 0.3rem;">Indiquez le nom de la personne à qui appartient réellement ce bureau (vous êtes son locataire).</small>
                    @error('real_owner_name') <div class="form-error error-real_owner_name">{{ $message }}</div> @enderror
                </div>
                <div class="form-field" id="contract_start_date_wrapper" style="display:{{ old('status') === 'locataire' ? 'block' : 'none' }};">
                    <label for="contract_start_date">Date de début de contrat de location</label>
                    <input id="contract_start_date" type="date" name="contract_start_date" value="{{ old('contract_start_date') }}">
                    @error('contract_start_date') <div class="form-error error-contract_start_date">{{ $message }}</div> @enderror
                </div>
                <div class="form-field" id="contract_end_date_wrapper" style="display:{{ old('status') === 'locataire' ? 'block' : 'none' }};">
                    <label for="contract_end_date">Date de fin de contrat de location</label>
                    <input id="contract_end_date" type="date" name="contract_end_date" value="{{ old('contract_end_date') }}">
                    @error('contract_end_date') <div class="form-error error-contract_end_date">{{ $message }}</div> @enderror
                </div>
                <div class="form-field">
                    <label for="office_number">Numéro de bureau</label>
                    <input id="office_number" type="text" name="office_number" value="{{ old('office_number') }}">
                    @error('office_number') <div class="form-error error-office_number">{{ $message }}</div> @enderror
                </div>
                <div class="form-field">
                    <label for="floor">Étage</label>
                    <input id="floor" type="number" name="floor" value="{{ old('floor') }}">
                    @error('floor') <div class="form-error error-floor">{{ $message }}</div> @enderror
                </div>
                <div class="form-field">
                    <label for="is_council_member">Membre du conseil ?</label>
                    <select id="is_council_member" name="is_council_member">
                        <option value="0" {{ old('is_council_member') === '0' ? 'selected' : '' }}>Non</option>
                        <option value="1" {{ old('is_council_member') === '1' ? 'selected' : '' }}>Oui</option>
                    </select>
                    @error('is_council_member') <div class="form-error error-is_council_member">{{ $message }}</div> @enderror
                </div>
                <button type="submit" class="btn-primary">Activer le compte</button>
            </form>
            <script>
                document.getElementById('status').addEventListener('change', function() {
                    const startWrapper = document.getElementById('contract_start_date_wrapper');
                    const endWrapper = document.getElementById('contract_end_date_wrapper');
                    const ownerWrapper = document.getElementById('real_owner_name_wrapper');
                    const startInput = document.getElementById('contract_start_date');
                    const endInput = document.getElementById('contract_end_date');
                    const ownerInput = document.getElementById('real_owner_name');
                    if (this.value === 'locataire') {
                        startWrapper.style.display = 'block';
                        endWrapper.style.display = 'block';
                        ownerWrapper.style.display = 'block';
                        startInput.setAttribute('required', 'required');
                        endInput.setAttribute('required', 'required');
                        ownerInput.setAttribute('required', 'required');
                    } else {
                        startWrapper.style.display = 'none';
                        endWrapper.style.display = 'none';
                        ownerWrapper.style.display = 'none';
                        startInput.removeAttribute('required');
                        endInput.removeAttribute('required');
                        ownerInput.removeAttribute('required');
                        startInput.value = '';
                        endInput.value = '';
                        ownerInput.value = '';
                    }
                });

                document.getElementById('has_mezzanine').addEventListener('change', function() {
                    const wrapper = document.getElementById('mezzanine_surface_wrapper');
                    const input = document.getElementById('mezzanine_surface');
                    if (this.value === '1') {
                        wrapper.style.display = 'block';
                        input.setAttribute('required', 'required');
                    } else {
                        wrapper.style.display = 'none';
                        input.removeAttribute('required');
                        input.value = '';
                    }
                });
            </script>
        </section>
    </main>
</body>
</html>