<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activation du compte</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="page-fade-in">
    <main class="auth-shell">
        <section class="form-glass auth-card">
            <div style="display:flex; justify-content:center; margin-bottom:1.5rem;">
                <div class="feature-badge">Activation</div>
            </div>
            <h1 style="margin:0; text-align:center; font-size:2rem;">Définir votre mot de passe</h1>
            <p style="margin:0.5rem 0 1.75rem; text-align:center; color:var(--color-text-muted);">Finalisez l'activation de votre compte</p>
            @include('partials.flash')
            <form method="POST" action="{{ route('activate.store', ['token' => $token]) }}" class="form-grid">
                @csrf
                <div class="form-field">
                    <label for="password">Nouveau mot de passe</label>
                    <input id="password" type="password" name="password" required>
                    @error('password') <div class="form-error error-password">{{ $message }}</div> @enderror
                </div>
                <div class="form-field">
                    <label for="password_confirmation">Confirmer le mot de passe</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required>
                </div>
                <button type="submit" class="btn-primary">Activer le compte</button>
            </form>
        </section>
    </main>
</body>
</html>
