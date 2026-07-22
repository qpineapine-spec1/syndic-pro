<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription syndic</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="page-fade-in">
    <main class="auth-shell">
        <section class="form-glass auth-card">
            <div style="display:flex; justify-content:center; margin-bottom:1.5rem;">
                <div class="feature-badge">Syndic Professionnel</div>
            </div>
            <h1 style="margin:0; text-align:center; font-size:2rem;">Créer un compte</h1>
            <p style="margin:0.5rem 0 1.75rem; text-align:center; color:var(--color-text-muted);">Inscription syndic</p>
            @include('partials.flash')
            <form method="POST" action="{{ route('register.store') }}" class="form-grid">
                @csrf

                <div class="form-field">
                    <label for="name">Nom complet</label>
                    <input id="name" name="name" value="{{ old('name') }}" required>
                    @error('name') <div class="form-error error-name">{{ $message }}</div> @enderror
                </div>

                <div class="form-field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required>
                    @error('email') <div class="form-error error-email">{{ $message }}</div> @enderror
                </div>

                <div class="form-field">
                    <label for="password">Mot de passe</label>
                    <input id="password" type="password" name="password" required>
                    @error('password') <div class="form-error error-password">{{ $message }}</div> @enderror
                </div>

                <div class="form-field">
                    <label for="password_confirmation">Confirmation du mot de passe</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required>
                </div>

                <div class="form-field">
                    <label for="property_name">Nom de l'immeuble</label>
                    <input id="property_name" name="property_name" value="{{ old('property_name') }}" required>
                    @error('property_name') <div class="form-error error-property_name">{{ $message }}</div> @enderror
                </div>

                <div class="form-field">
                    <label for="property_address">Adresse de l'immeuble</label>
                    <input id="property_address" name="property_address" value="{{ old('property_address') }}" required>
                    @error('property_address') <div class="form-error error-property_address">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn-primary" style="margin-top:0.5rem;">Créer le compte</button>
            </form>
            <div style="margin-top:1.5rem; text-align:center;">
                <a href="{{ route('login') }}" class="btn-secondary" style="width:100%;">Déjà inscrit ? Se connecter</a>
            </div>
        </section>
    </main>
</body>
</html>
