<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compte en attente d'activation</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="page-fade-in">
    <main class="auth-shell">
        <section class="form-glass auth-card" style="text-align:center;">
            <div style="display:flex; justify-content:center; margin-bottom:1.5rem;">
                <div class="feature-badge">Activation</div>
            </div>
            <h1 style="margin:0; font-size:2rem;">Compte non vérifié</h1>
            <p style="margin:1.25rem 0 1.75rem; color:var(--color-text-muted);">
                Votre compte n’est pas encore activé. Un lien d’activation a été envoyé à <strong>{{ $email }}</strong>.
            </p>
            @include('partials.flash')
            <form method="POST" action="{{ route('activate.resend') }}" class="form-grid">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">
                <button type="submit" class="btn-primary" style="width:100%;">Renvoyer le lien d'activation</button>
            </form>
            <div style="margin-top:1rem;">
                <a href="{{ route('login') }}" class="btn-secondary" style="width:100%;">Retour à la connexion</a>
            </div>
        </section>
    </main>
</body>
</html>
