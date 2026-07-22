<?php $__env->startSection('title', 'Règlement de copropriété'); ?>

<?php $__env->startSection('content'); ?>
<div class="card-glass" style="padding:2rem;">
    <h2>Règlement de copropriété</h2>

    <?php echo $__env->make('partials.flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php if($property->reglement_fichier): ?>
        <div class="alert alert-warning" style="margin-bottom:1rem;">
            <strong>Un règlement est déjà en ligne.</strong> Le téléverser à nouveau remplacera le fichier actuel, visible par tous les copropriétaires et depuis l'accueil public.
        </div>
        <p style="margin-bottom:1.2rem;">
            <a href="<?php echo e(route('properties.reglement.download', $property)); ?>" class="btn-secondary">Voir le fichier actuel</a>
        </p>
    <?php else: ?>
        <p style="margin-bottom:1.2rem; color:var(--color-muted, #6b7280);">
            Aucun règlement n'a encore été téléversé. Une fois envoyé, il sera visible et téléchargeable par tous les copropriétaires ainsi que depuis la page d'accueil publique.
        </p>
    <?php endif; ?>

    <form action="<?php echo e(route('properties.reglement.upload', $property)); ?>" method="post" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <div>
            <label for="reglement">Fichier du règlement</label>
            <input type="file" name="reglement" id="reglement" required />
        </div>
        <?php $__errorArgs = ['reglement'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <div class="alert alert-danger" style="margin-top:1rem;"><?php echo e($message); ?></div>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

        <div style="margin-top:1rem;">
            <button class="btn-primary">Téléverser</button>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Hp\Desktop\Project-syndic\nouveau-projet\resources\views/properties/reglement-upload.blade.php ENDPATH**/ ?>