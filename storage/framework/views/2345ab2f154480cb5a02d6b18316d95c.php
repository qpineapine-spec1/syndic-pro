

<?php $__env->startSection('title', 'Ma cotisation'); ?>

<?php $__env->startSection('content'); ?>
    <section class="page-header">
        <div>
            <div class="feature-badge">Cotisation</div>
            <h1 class="page-title">Ma cotisation</h1>
        </div>
        <a href="<?php echo e(route('dashboard')); ?>" class="btn-secondary">Retour</a>
    </section>

    <section class="card-glass" style="padding:1.5rem; margin-top:1.5rem;">
        <?php if($contribution): ?>
            <div style="display:grid; gap:0.8rem; margin-bottom:1.5rem;">
                <div><strong>Contribution attribuée :</strong> <?php echo e($contribution->budget?->year ?? '—'); ?></div>
                <div><strong>Tantième :</strong> <?php echo e(number_format($contribution->tantieme, 2, ',', ' ')); ?> %</div>
                <div><strong>Montant annuel :</strong> <?php echo e(number_format($contribution->montant_annuel, 2, ',', ' ')); ?> €</div>
                <div><strong>Montant mensuel :</strong> <?php echo e(number_format($contribution->montant_mensuel, 2, ',', ' ')); ?> €</div>
            </div>
        <?php else: ?>
            <div class="dropdown-empty">Aucune contribution n’a encore été attribuée à votre bureau.</div>
        <?php endif; ?>

        <h2 style="margin-top:0;">Cotisation non payée</h2>
        <?php if(!$unpaidContribution): ?>
            <div class="dropdown-empty">Aucune cotisation impayée.</div>
        <?php else: ?>
            <div class="expense-table-wrap">
                <table class="expense-table">
                    <thead>
                        <tr>
                            <th>Mois</th>
                            <th>Montant</th>
                            <th>Date d'échéance</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo e($cycleStart ? ucfirst($cycleStart->translatedFormat('F Y')) : '—'); ?></td>
                            <td><?php echo e(number_format($unpaidContribution->montant_mensuel, 2, ',', ' ')); ?> MAD</td>
                            <td><?php echo e($dueDate?->format('d/m/Y') ?? '—'); ?></td>
                            <td>
                                <span class="status-badge <?php echo e($contributionStatus === 'en_retard' ? 'status-badge--late' : 'status-badge--pending'); ?>">
                                    <?php echo e($contributionStatus === 'en_retard' ? 'En retard' : 'Non payé'); ?>

                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <h2 style="margin-top:1.5rem;">Historique de mes cotisations payées</h2>
        <?php if($paymentHistory->isEmpty()): ?>
            <div class="dropdown-empty">Aucun paiement de cotisation enregistré pour le moment.</div>
        <?php else: ?>
            <div class="expense-table-wrap">
                <table class="expense-table">
                    <thead>
                        <tr>
                            <th>Mois</th>
                            <th>Date de paiement</th>
                            <th>Montant</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $paymentHistory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e(ucfirst($payment->paid_at->translatedFormat('F Y'))); ?></td>
                                <td><?php echo e($payment->paid_at->format('d/m/Y')); ?></td>
                                <td><?php echo e(number_format($payment->amount, 2, ',', ' ')); ?> MAD</td>
                                <td><span class="status-badge status-badge--paid">Payé</span></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </section>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Hp\Desktop\Project-syndic\nouveau-projet\resources\views/contributions/owner.blade.php ENDPATH**/ ?>