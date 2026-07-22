

<?php $__env->startSection('title', 'Dépenses'); ?>

<?php $__env->startSection('content'); ?>
        <section class="page-header">
            <div>
                <div class="feature-badge">Dépenses</div>
                <h1 class="page-title">Historique des dépenses</h1>
            </div>
            <a href="<?php echo e(route('dashboard')); ?>" class="btn-secondary">Retour</a>
        </section>

        <?php if(session('success')): ?>
            <div class="alert-success"><?php echo e(session('success')); ?></div>
        <?php endif; ?>

        <section class="card-glass" style="padding:1.5rem;">
            <div class="expense-table-wrap">
                <table class="expense-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Nom de la dépense</th>
                            <th>Montant</th>
                            <th>Facture</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $allExpenses = $fixedExpenses->concat($variableExpenses)->sortByDesc(fn ($e) => $e->paid_at ?? $e->expense_date); ?>
                        <?php $__empty_1 = true; $__currentLoopData = $allExpenses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $expense): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><?php echo e(($expense->paid_at ?? $expense->expense_date)?->format('d/m/Y')); ?></td>
                                <td><?php echo e($expense->label ?: ($expense->categorie ?? '—')); ?></td>
                                <td><?php echo e(number_format($expense->amount, 2, ',', ' ')); ?> MAD</td>
                                <td>
                                    <?php if($expense->fichier_facture): ?>
                                        <a href="<?php echo e(route('expenses.download-facture', $expense)); ?>" class="facture-link">Voir facture</a>
                                    <?php else: ?>
                                        <span style="color: var(--color-text-muted);">Aucune facture</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr class="is-empty-row">
                                <td colspan="4">Aucune dépense n'a été effectuée jusqu'à maintenant par le syndic.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Hp\Desktop\Project-syndic\nouveau-projet\resources\views/expenses/council.blade.php ENDPATH**/ ?>