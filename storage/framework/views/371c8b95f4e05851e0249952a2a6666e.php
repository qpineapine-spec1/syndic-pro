

<?php $__env->startSection('title', 'Gestion des copropriétaires'); ?>

<?php $__env->startSection('content'); ?>
    <section class="page-header">
        <div>
            <div class="feature-badge">Gestion des copropriétaires</div>
            <h1 class="page-title" style="margin-top:0.7rem;">Liste des copropriétaires</h1>
        </div>
    </section>

    <section class="card-glass" style="padding:1.5rem; margin-top:1.5rem;">
        <?php if($owners->isEmpty()): ?>
            <p>Aucun copropriétaire trouvé pour votre propriété.</p>
        <?php else: ?>
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="text-align:left; padding:0.8rem; border-bottom:1px solid rgba(0,0,0,0.12);">Nom</th>
                        <th style="text-align:left; padding:0.8rem; border-bottom:1px solid rgba(0,0,0,0.12);">Email</th>
                        <th style="text-align:left; padding:0.8rem; border-bottom:1px solid rgba(0,0,0,0.12);">Statut</th>
                        <th style="text-align:left; padding:0.8rem; border-bottom:1px solid rgba(0,0,0,0.12);">Téléphone</th>
                        <th style="text-align:left; padding:0.8rem; border-bottom:1px solid rgba(0,0,0,0.12);">Lot</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $owners; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $owner): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td style="padding:0.8rem; border-bottom:1px solid rgba(0,0,0,0.08);"><?php echo e($owner->user->name ?? '—'); ?></td>
                            <td style="padding:0.8rem; border-bottom:1px solid rgba(0,0,0,0.08);"><?php echo e($owner->user->email ?? '—'); ?></td>
                           <td style="padding:0.8rem; border-bottom:1px solid rgba(0,0,0,0.08);">
                                <?php if($owner->status === 'locataire'): ?>
                                    <span class="feature-badge">En location</span>
                                    <div style="margin-top:0.4rem; font-size:0.85rem; color:var(--color-text-muted); line-height:1.5;">
                                        <?php if($owner->real_owner_name): ?>
                                            <div><strong>Propriétaire réel :</strong> <?php echo e($owner->real_owner_name); ?></div>
                                        <?php endif; ?>
                                        <?php if($owner->tenant): ?>
                                            <div><strong>Locataire :</strong> <?php echo e($owner->tenant->tenant_name ?? '—'); ?> (<?php echo e($owner->tenant->tenant_phone ?? '—'); ?>)</div>
                                            <div><strong>Contrat :</strong> <?php echo e(optional($owner->tenant->contract_start_date)->format('d/m/Y') ?? '—'); ?> → <?php echo e(optional($owner->tenant->contract_end_date)->format('d/m/Y') ?? '—'); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    Propriétaire
                                <?php endif; ?>
                            </td>
                            <td style="padding:0.8rem; border-bottom:1px solid rgba(0,0,0,0.08);"><?php echo e($owner->telephone ?? '—'); ?></td>
                            <td style="padding:0.8rem; border-bottom:1px solid rgba(0,0,0,0.08);"><?php echo e($owner->lot_surface ?? '—'); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Hp\Desktop\Project-syndic\nouveau-projet\resources\views/owners/index.blade.php ENDPATH**/ ?>