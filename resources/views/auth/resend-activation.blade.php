<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renvoyer le lien d'activation</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="page-fade-in">
    <main class="auth-shell">
        <section class="form-glass auth-card">
            <div style="display:flex; justify-content:center; margin-bottom:1.5rem;">
                <div class="feature-badge">Activation</div>
            </div>
            <h1 style="margin:0; text-align:center; font-size:2rem;">Renvoyer le lien</h1>
            <p style="margin:0.5rem 0 1.75rem; text-align:center; color:var(--color-text-muted);">Recevez un nouveau lien d'activation</p>
            @include('partials.flash')
            <form method="POST" action="{{ route('activate.resend') }}" class="form-grid">
                @csrf
                <div class="form-field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" required>
                </div>
                <button type="submit" class="btn-primary">Envoyer le lien</button>
            </form>
            <div style="margin-top:1.5rem; text-align:center;">
                <a href="{{ route('login') }}" class="btn-secondary" style="width:100%;">Retour à la connexion</a>
            </div>
        </section>
    </main>
</body>
</html>
