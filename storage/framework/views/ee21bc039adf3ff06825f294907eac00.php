

<?php $__env->startSection('content'); ?>
<div class="card-glass" style="padding:2rem;">
    <h2>Import terminé</h2>

    <h3>Résultats</h3>
    <ul>
        <li>Copropriétaires créés (invitations) : <?php echo e(count($results['owners_created'] ?? [])); ?></li>
        <li>Copropriétaires ignorés (existants) : <?php echo e(count($results['owners_skipped'] ?? [])); ?></li>
        <li>Prestataires : <?php echo e(count($results['service_providers'] ?? [])); ?></li>
        <li>Budget : <?php echo e($results['budget'] ?? 'aucun'); ?></li>
        <li>Lignes de charges importées : <?php echo e(count($results['expenses'] ?? [])); ?></li>
    </ul>
    <div style="margin-top:1rem;">
        <a href="<?php echo e(route('dashboard')); ?>" class="btn-secondary">Retour au tableau de bord</a>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Hp\Desktop\Project-syndic\nouveau-projet\resources\views/import/confirmed.blade.php ENDPATH**/ ?>