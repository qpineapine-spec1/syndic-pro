<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="page-fade-in">
    <main class="auth-shell">
        <section class="form-glass auth-card">
            <div style="display:flex; justify-content:center; margin-bottom:1.5rem;">
                <div class="feature-badge">Syndic Professionnel</div>
            </div>
            <h1 style="margin:0; text-align:center; font-size:2rem;">Connexion</h1>
            <p style="margin:0.5rem 0 1.75rem; text-align:center; color:var(--color-text-muted);">Accédez à votre espace</p>
            <?php echo $__env->make('partials.flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php if($errors->any()): ?>
                <div class="alert-error" role="alert" style="margin-bottom: 1.5rem; padding: 1rem; border-radius: 0.5rem; background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24;">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <p style="margin: 0.5rem 0; font-size: 0.95rem;"><?php echo e($error); ?></p>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="<?php echo e(route('login')); ?>" class="form-grid">
                <?php echo csrf_field(); ?>
                <div class="form-field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" required>
                </div>
                <div class="form-field">
                    <label for="password">Mot de passe</label>
                    <input id="password" type="password" name="password" required>
                </div>
                <button type="submit" class="btn-primary">Se connecter</button>
            </form>
            <div style="margin-top:1.5rem; text-align:center; display:flex; flex-direction:column; gap:0.75rem;">
                <a href="<?php echo e(route('register')); ?>" class="btn-secondary" style="width:100%;">Créer un compte</a>
                <a href="<?php echo e(route('activate.resend.form')); ?>" class="btn-secondary" style="width:100%;">Renvoyer le lien d'activation</a>
            </div>
        </section>
    </main>
</body>
</html>
<?php /**PATH C:\Users\Hp\Desktop\Project-syndic\nouveau-projet\resources\views/auth/login.blade.php ENDPATH**/ ?>