

<?php $__env->startSection('content'); ?>
<div class="card-glass" style="padding:2rem;">
    <h2>Prévisualisation de l'import</h2>

    <h3>Copropriétaires</h3>
    <table>
        <thead><tr><th>Nom</th><th>Prenom</th><th>Email</th><th>Téléphone</th></tr></thead>
        <tbody>
        <?php $__currentLoopData = ($data['owners'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $o): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e($o['last_name'] ?? ''); ?></td>
                <td><?php echo e($o['first_name'] ?? ''); ?></td>
                <td><?php echo e($o['email'] ?? ''); ?></td>
                <td><?php echo e($o['phone'] ?? ''); ?></td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>

    <h3>Prestataires</h3>
    <table>
        <thead><tr><th>Nom</th><th>Debut</th><th>Fin</th><th>Mensuel</th></tr></thead>
        <tbody>
        <?php $__currentLoopData = ($data['service_providers'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e($s['name'] ?? ''); ?></td>
                <td><?php echo e($s['contract_start'] ?? ''); ?></td>
                <td><?php echo e($s['contract_end'] ?? ''); ?></td>
                <td><?php echo e($s['monthly_amount'] ?? ''); ?></td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>

    <form action="<?php echo e(route('import.confirm')); ?>" method="post">
        <?php echo csrf_field(); ?>
        <button class="btn-primary">Confirmer et Importer</button>
        <a href="<?php echo e(route('import.upload')); ?>" class="btn-secondary">Annuler</a>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Hp\Desktop\Project-syndic\nouveau-projet\resources\views/import/preview.blade.php ENDPATH**/ ?>