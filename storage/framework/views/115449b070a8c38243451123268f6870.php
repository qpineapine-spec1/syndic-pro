

<?php $__env->startSection('title', 'Budgets'); ?>

<?php $__env->startSection('content'); ?>
        <section class="page-header">
            <div>
                <div class="feature-badge">Budgets</div>
                <h1 class="page-title">Prévision budgétaire</h1>
            </div>
            <a href="<?php echo e(route('budgets.create')); ?>" class="btn-primary">Nouveau budget</a>
        </section>

        <section class="card-glass" style="padding:1.5rem;">
            <h2>Historique des budgets</h2>
            <?php if($budgets->isEmpty()): ?>
                <div class="dropdown-empty">Aucun budget enregistré pour cette copropriété.</div>
            <?php else: ?>
                <table style="width:100%; margin-top:1rem; border-collapse:collapse;">
                    <thead>
                        <tr style="border-bottom:1px solid rgba(27,58,92,0.1); text-align:left;">
                            <th style="padding:0.6rem;">Année</th>
                            <th style="padding:0.6rem;">Charges fixes</th>
                            <th style="padding:0.6rem;">Charges variables</th>
                            <th style="padding:0.6rem;">Validé</th>
                            <th style="padding:0.6rem;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $budgets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $budget): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr style="border-bottom:1px solid rgba(27,58,92,0.05);">
                                <td style="padding:0.6rem;"><?php echo e($budget->year); ?></td>
                                <td style="padding:0.6rem;"><?php echo e(number_format($budget->fixed_charges_total, 2, ',', ' ')); ?> €</td>
                                <td style="padding:0.6rem;"><?php echo e(number_format($budget->variable_charges_total, 2, ',', ' ')); ?> €</td>
                                <td style="padding:0.6rem;"><?php echo e($budget->is_valid ? 'Oui' : 'Non'); ?></td>
                                <td style="padding:0.6rem;">
                                    <?php if(! $budget->is_valid): ?>
                                        <form method="POST" action="<?php echo e(route('budgets.validate', $budget)); ?>" style="display:inline">
                                            <?php echo csrf_field(); ?>
                                            <button class="btn-primary">Valider</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="feature-badge">Validé</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <hr style="margin:1.2rem 0;">

            <?php if($predictionAvailable): ?>
                <div>
                    <h3>Prédiction disponible</h3>
                    <div class="card-glass">La fonctionnalité de prédiction est activée car 3 budgets validés consécutifs existent. Aucune valeur ML n'est calculée automatiquement ici — implémentez le service ML séparément si nécessaire.</div>
                </div>
            <?php else: ?>
                <div class="disabled-feature" style="padding:1rem;">La section de prédiction est désactivée — il faut au moins 3 budgets validés consécutifs pour l'activer.</div>
            <?php endif; ?>
        </section>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Hp\Desktop\Project-syndic\nouveau-projet\resources\views/budgets/index.blade.php ENDPATH**/ ?>