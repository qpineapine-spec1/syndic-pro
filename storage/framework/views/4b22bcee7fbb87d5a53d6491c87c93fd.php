<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activation du compte</title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="page-fade-in">
    <main class="auth-shell">
        <section class="form-glass auth-card">
            <div style="display:flex; justify-content:center; margin-bottom:1.5rem;">
                <div class="feature-badge">Activation</div>
            </div>
            <h1 style="margin:0; text-align:center; font-size:2rem;">Définir votre mot de passe</h1>
            <p style="margin:0.5rem 0 1.75rem; text-align:center; color:var(--color-text-muted);">Finalisez l'activation de votre compte</p>
            <?php echo $__env->make('partials.flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <form method="POST" action="<?php echo e(route('activate.store', ['token' => $token])); ?>" class="form-grid">
                <?php echo csrf_field(); ?>
                <div class="form-field">
                    <label for="password">Nouveau mot de passe</label>
                    <input id="password" type="password" name="password" required>
                    <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="form-error error-password"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
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
<?php /**PATH C:\Users\Hp\Desktop\Project-syndic\nouveau-projet\resources\views/auth/activate.blade.php ENDPATH**/ ?>