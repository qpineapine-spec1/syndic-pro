

<?php $__env->startSection('title', 'Locataire / bureau'); ?>

<?php $__env->startSection('content'); ?>
    <section class="page-header">
        <div>
            <div class="feature-badge">Locataire &amp; bureau</div>
            <h1 class="page-title">
                <?php if(auth()->user()->role === 'syndic'): ?>
                    Gestion des locataires
                <?php else: ?>
                    État de votre bureau
                <?php endif; ?>
            </h1>
        </div>
        <?php if(auth()->user()->role !== 'syndic'): ?>
            <a href="<?php echo e(route('profile.show')); ?>" class="btn-secondary">Retour au profil</a>
        <?php endif; ?>
    </section>

    <?php if(session('status')): ?>
        <div class="dashboard-card" style="margin-top:1rem;"><?php echo e(session('status')); ?></div>
    <?php endif; ?>

    <?php if(auth()->user()->role === 'syndic'): ?>
        
        <section class="card-glass" style="padding:1.5rem; margin-top:1.5rem; overflow-x:auto;">
            <?php if($tenants->isEmpty()): ?>
                <div class="dropdown-empty">Aucun locataire disponible pour le moment.</div>
            <?php else: ?>
                <table class="table" style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th style="text-align:left; padding:0.5rem;">Bureau</th>
                            <th style="text-align:left; padding:0.5rem;">Propriétaire</th>
                            <th style="text-align:left; padding:0.5rem;">Locataire</th>
                            <th style="text-align:left; padding:0.5rem;">Téléphone</th>
                            <th style="text-align:left; padding:0.5rem;">Début contrat</th>
                            <th style="text-align:left; padding:0.5rem;">Fin contrat</th>
                            <th style="text-align:left; padding:0.5rem;">État</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $tenants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td style="padding:0.5rem;"><?php echo e($t->owner->office_number ?? '—'); ?></td>
                                <td style="padding:0.5rem;"><?php echo e($t->owner->real_owner_name ?? '—'); ?></td>
                                <td style="padding:0.5rem;"><?php echo e($t->tenant_name ?? '—'); ?></td>
                                <td style="padding:0.5rem;"><?php echo e($t->tenant_phone ?? '—'); ?></td>
                                <td style="padding:0.5rem;"><?php echo e(optional($t->contract_start_date)->format('d/m/Y') ?? '—'); ?></td>
                                <td style="padding:0.5rem;"><?php echo e(optional($t->contract_end_date)->format('d/m/Y') ?? '—'); ?></td>
                                <td style="padding:0.5rem;">
                                    <span class="feature-badge"><?php echo e($t->is_active ? 'En location' : 'Contrat terminé'); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    <?php else: ?>
        
        <section class="card-glass" style="padding:1.5rem; margin-top:1.5rem;">
            <?php if(isset($tenant) && $tenant): ?>
                <div style="display:grid; gap:0.7rem;">
                    <div><strong>Nom du locataire :</strong> <?php echo e($tenant->tenant_name ?? '—'); ?></div>
                    <div><strong>Téléphone du locataire :</strong> <?php echo e($tenant->tenant_phone ?? '—'); ?></div>
                    <div><strong>Date de début de contrat :</strong> <?php echo e(optional($tenant->contract_start_date)->format('d/m/Y') ?? '—'); ?></div>
                    <div><strong>Date de fin de contrat :</strong> <?php echo e(optional($tenant->contract_end_date)->format('d/m/Y') ?? '—'); ?></div>
                    <div><strong>État du bureau :</strong> <?php echo e($owner->status === 'locataire' ? 'En location' : 'Propriétaire'); ?></div>
                </div>
            <?php else: ?>
                <div class="dropdown-empty" style="margin-bottom:1rem;">Aucun contrat locatif n'est actuellement enregistré pour ce bureau.</div>

                <p style="color:var(--color-text-muted); margin-bottom:1rem;">Si vous louez votre bureau, vous pouvez enregistrer votre locataire ci-dessous. Le bureau passera automatiquement à l'état « En location » et apparaîtra dans le tableau du syndic.</p>

                <form action="<?php echo e(route('tenants.store')); ?>" method="POST" style="display:grid; gap:0.9rem;">
                    <?php echo csrf_field(); ?>
                    <div style="display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:0.9rem;">
                        <div style="display:grid; gap:0.35rem;">
                            <label for="tenant_name">Nom et prénom du locataire</label>
                            <input id="tenant_name" type="text" name="tenant_name" required class="form-control">
                        </div>
                        <div style="display:grid; gap:0.35rem;">
                            <label for="tenant_phone">Numéro de téléphone</label>
                            <input id="tenant_phone" type="text" name="tenant_phone" required class="form-control">
                        </div>
                        <div style="display:grid; gap:0.35rem;">
                            <label for="contract_start_date">Date de début de contrat</label>
                            <input id="contract_start_date" type="date" name="contract_start_date" required class="form-control">
                        </div>
                        <div style="display:grid; gap:0.35rem;">
                            <label for="contract_end_date">Date de fin de contrat</label>
                            <input id="contract_end_date" type="date" name="contract_end_date" required class="form-control">
                        </div>
                    </div>
                    <div>
                        <button type="submit" class="btn-primary">Ajouter le locataire</button>
                    </div>
                </form>
            <?php endif; ?>
        </section>
    <?php endif; ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Hp\Desktop\Project-syndic\nouveau-projet\resources\views/tenants/index.blade.php ENDPATH**/ ?>