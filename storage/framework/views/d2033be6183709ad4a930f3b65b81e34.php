

<?php $__env->startSection('title', 'Dépenses'); ?>

<?php $__env->startSection('content'); ?>
        <section class="page-header">
            <div>
                <div class="feature-badge">Dépenses</div>
                <h1 class="page-title">Suivi des dépenses</h1>
            </div>
            <a href="<?php echo e(route('dashboard')); ?>" class="btn-secondary">Retour</a>
        </section>

        <?php if(session('success')): ?>
            <div class="alert-success"><?php echo e(session('success')); ?></div>
        <?php endif; ?>
        <?php if($errors->any()): ?>
            <div class="alert-danger">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div><?php echo e($error); ?></div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>

        <section class="card-glass" style="padding:1.5rem;">

            
            <div class="expense-section-header">
                <h2>Dépenses Fixes</h2>
                <?php if($cycleStart): ?>
                    <span class="feature-badge" style="margin-top:0;">Cycle en cours depuis le <?php echo e($cycleStart->format('d/m/Y')); ?></span>
                <?php endif; ?>
            </div>

            <div class="expense-table-wrap" style="margin-bottom:1rem;">
                <table class="expense-table expense-table--fixed">
                    <thead>
                        <tr>
                            <th>✓</th>
                            <th>Nom de la dépense</th>
                            <th>Montant</th>
                            <th>Facture</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $fixedExpenses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $expense): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td>
                                    <?php if(auth()->user()->role === 'syndic'): ?>
                                        <form action="<?php echo e(route('expenses.toggle-paid', $expense)); ?>" method="POST">
                                            <?php echo csrf_field(); ?>
                                            <input
                                                type="checkbox"
                                                name="paid"
                                                value="1"
                                                class="expense-checkbox"
                                                <?php echo e($expense->isPaidThisCycle($cycleStart) ? 'checked' : ''); ?>

                                                onchange="this.form.submit()"
                                                aria-label="Marquer <?php echo e($expense->label); ?> comme payée ce mois-ci"
                                            >
                                        </form>
                                    <?php else: ?>
                                        <input type="checkbox" class="expense-checkbox" disabled <?php echo e($expense->isPaidThisCycle($cycleStart) ? 'checked' : ''); ?>>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($expense->label); ?></td>
                                <td><?php echo e(number_format($expense->amount, 2, ',', ' ')); ?> MAD</td>
                                <td>
                                    <?php if($expense->fichier_facture): ?>
                                        <a href="<?php echo e(route('expenses.download-facture', $expense)); ?>" class="facture-link">Voir facture</a>
                                    <?php else: ?>
                                        <span style="color: var(--color-text-muted);">Aucune facture</span>
                                    <?php endif; ?>
                                </td>
                                <td class="expense-action-cell">
                                    <?php if(auth()->user()->role === 'syndic'): ?>
                                        <?php if($expense->fichier_facture): ?>
                                            <button type="button" class="btn-secondary btn-sm" disabled>Uploadé</button>
                                        <?php else: ?>
                                            <form action="<?php echo e(route('expenses.upload', $expense)); ?>" method="POST" enctype="multipart/form-data">
                                                <?php echo csrf_field(); ?>
                                                <button type="button" class="btn-secondary btn-sm" onclick="this.nextElementSibling.click()">Uploader</button>
                                                <input type="file" name="fichier_facture" style="display:none" onchange="this.form.submit()" required>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr class="is-empty-row">
                                <td colspan="5">Aucune dépense fixe enregistrée pour cette copropriété.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            
            <div class="expense-section-header">
                <h2>Dépenses Variables</h2>
                <?php if(auth()->user()->role === 'syndic'): ?>
                    <button type="button" class="add-expense-btn" id="add-expense-toggle" aria-expanded="false" aria-label="Ajouter une charge variable" <?php echo e($variableLimitReached ? 'disabled' : ''); ?>>+</button>
                <?php endif; ?>
            </div>

            <?php if($variableCeiling !== null): ?>
                <div class="variable-budget-summary">
                    <span>Budget fixé par l'assemblée générale : <strong><?php echo e(number_format($variableCeiling, 2, ',', ' ')); ?> MAD</strong></span>
                    <span>Déjà utilisé : <strong><?php echo e(number_format($variableSpent, 2, ',', ' ')); ?> MAD</strong></span>
                    <span>Restant : <strong class="<?php echo e($variableRemaining <= $variableCeiling * 0.1 ? 'is-low' : ''); ?>"><?php echo e(number_format($variableRemaining, 2, ',', ' ')); ?> MAD</strong></span>
                </div>
            <?php endif; ?>

            <?php if($variableLimitReached): ?>
                <div class="alert-warning">
                    Vous avez atteint le montant fixé pour les charges variables. Vous ne pouvez plus créer de nouvelle dépense variable pour ce cycle.
                </div>
            <?php endif; ?>

            <?php if(auth()->user()->role === 'syndic' && !$variableLimitReached): ?>
                <div class="variable-expense-form-inline" id="variable-expense-form">
                    <form action="<?php echo e(route('expenses.store')); ?>" method="POST" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
                        <div class="form-row">
                            <label>
                                Catégorie
                                <select name="categorie" id="variable-category" required>
                                    <option value="" selected>Choisir votre catégorie</option>
                                    <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $labelText): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($value); ?>"><?php echo e($labelText); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </label>
                        </div>

                        <div id="variable-amount-step" class="reveal-step">
                            <label>
                                Montant (MAD)
                                <input type="number" step="0.01" min="0.01" name="amount" id="variable-amount" <?php if($variableRemaining !== null): ?> max="<?php echo e($variableRemaining); ?>" <?php endif; ?>>
                            </label>
                            <label>
                                Description (optionnel)
                                <input type="text" name="description" maxlength="255" placeholder="Détail de la dépense">
                            </label>
                        </div>

                        <div id="variable-upload-step" class="reveal-step">
                            <label>
                                Facture (optionnel)
                                <input type="file" name="fichier_facture">
                            </label>
                            <button type="submit" class="btn-primary">Valider</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <div class="expense-table-wrap">
                <table class="expense-table expense-table--variable">
                    <thead>
                        <tr>
                            <th>Catégorie</th>
                            <th>Montant</th>
                            <th>Description</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $variableExpenses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $expense): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><?php echo e($expense->categorie ?? '—'); ?></td>
                                <td><?php echo e(number_format($expense->amount, 2, ',', ' ')); ?> MAD</td>
                                <td><?php echo e($expense->label ?: '—'); ?></td>
                                <td class="expense-action-cell">
                                    <?php if($expense->fichier_facture): ?>
                                        <a href="<?php echo e(route('expenses.download-facture', $expense)); ?>" class="facture-link">Voir facture</a>
                                    <?php else: ?>
                                        <span style="color: var(--color-text-muted);">Pas de facture uploadée</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr class="is-empty-row">
                                <td colspan="4">Aucune dépense variable enregistrée depuis le dernier import de l'assemblée générale.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Hp\Desktop\Project-syndic\nouveau-projet\resources\views/expenses/index.blade.php ENDPATH**/ ?>