<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lien d'activation invalide</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="page-fade-in">
    <main class="auth-shell">
        <section class="form-glass auth-card" style="text-align:center;">
            <div style="display:flex; justify-content:center; margin-bottom:1.5rem;">
                <div class="feature-badge">Activation</div>
            </div>
            <h1 style="margin:0; font-size:2rem;">Lien d'activation invalide</h1>
            <p style="margin:1.25rem 0 1.75rem; color:var(--color-text-muted);">Ce lien est expiré, déjà utilisé, ou invalide.</p>
            <a href="{{ route('activate.resend.form') }}" class="btn-primary" style="width:100%;">Renvoyer le lien</a>
        </section>
    </main>
</body>
</html>
