

<?php $__env->startSection('title', 'Réclamations'); ?>

<?php $__env->startSection('content'); ?>
        <style>
            /* === Réclamations : styles scopés à cette page uniquement === */
            .rc-page .page-header { margin-bottom: 1.25rem; }

            .rc-addbar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                padding: 0.9rem 1.25rem;
                margin-bottom: 1rem;
            }
            .rc-addbar__label { font-weight: 700; color: var(--color-primary); font-size: 0.95rem; }
            .rc-addbar__toggle {
                width: 2.35rem;
                height: 2.35rem;
                min-width: 2.35rem;
                padding: 0;
                font-size: 1.2rem;
                line-height: 1;
                border-radius: var(--radius-sm);
            }

            #complaint-form-wrapper { padding: 0 1.25rem 1.25rem; }

            .rc-table-card { padding: 0; overflow: hidden; }
            .rc-table-scroll { overflow-x: auto; }

            .rc-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
                min-width: 780px;
            }
            .rc-table thead th {
                text-align: left;
                padding: 0.85rem 1rem;
                font-size: 0.78rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: var(--color-text-muted);
                border-bottom: 1px solid var(--color-border);
                white-space: nowrap;
            }
            .rc-table tbody td {
                padding: 0.85rem 1rem;
                border-bottom: 1px solid rgba(27, 58, 92, 0.06);
                vertical-align: middle;
            }
            .rc-table tbody tr:last-child td { border-bottom: none; }
            .rc-table tbody tr:hover { background: rgba(217, 164, 65, 0.05); }

            .rc-subject { font-weight: 600; color: var(--color-text); }
            .rc-desc { color: var(--color-text-muted); font-size: 0.85rem; margin-top: 0.2rem; }
            .rc-muted { color: var(--color-text-muted); font-size: 0.85rem; }

            /* Ligne d'actions générique : aligne tout sur un même axe horizontal, centré verticalement */
            .rc-row {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                flex-wrap: wrap;
            }
            .rc-col {
                display: flex;
                flex-direction: column;
                gap: 0.4rem;
            }

            /* Uniformisation stricte : boutons + select partagent la même hauteur, le même rayon et le même padding */
            .rc-control {
                height: 2.35rem;
                border-radius: var(--radius-sm);
                padding: 0 0.9rem;
                font-size: 0.88rem;
                font-weight: 600;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                line-height: 1;
                box-sizing: border-box;
                white-space: nowrap;
            }
            a.rc-control, button.rc-control { cursor: pointer; }

            .rc-btn-primary {
                background: linear-gradient(135deg, var(--color-primary), #244b6e);
                color: #fff;
                border: 1px solid transparent;
                box-shadow: 0 6px 14px rgba(27, 58, 92, 0.18);
                transition: all 0.2s ease;
            }
            .rc-btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 10px 18px rgba(27, 58, 92, 0.24); }

            .rc-btn-secondary {
                background: transparent;
                border: 1px solid var(--color-accent);
                color: var(--color-primary);
                text-decoration: none;
                transition: all 0.2s ease;
            }
            .rc-btn-secondary:hover:not(:disabled) { transform: translateY(-1px); background: rgba(217, 164, 65, 0.08); }

            .rc-select {
                border: 1px solid var(--color-border);
                background: #fff;
                color: var(--color-text);
                min-width: 128px;
            }

            .rc-file {
                height: 2.35rem;
                display: inline-flex;
                align-items: center;
                font-size: 0.82rem;
                max-width: 170px;
            }

            .rc-badge { margin-top: 0; }

            @media (max-width: 720px) {
                .rc-addbar { flex-wrap: wrap; }
                .rc-row { gap: 0.4rem; }
                .rc-file { max-width: 140px; }
                .rc-table { min-width: 680px; }
            }
        </style>

        <section class="page-header rc-page">
            <div>
                <div class="feature-badge">Réclamations</div>
                <h1 class="page-title">Suivi des réclamations</h1>
            </div>
            <a href="<?php echo e(route('dashboard')); ?>" class="btn-secondary">Retour</a>
        </section>

        <?php echo $__env->make('partials.flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <?php if(auth()->user()->role === 'copropriétaire' && auth()->user()->owner): ?>
            <section class="card-glass rc-addbar">
                <span class="rc-addbar__label">Nouvelle réclamation</span>
                <button type="button" id="toggle-complaint-form" class="btn-secondary rc-addbar__toggle">+</button>
            </section>

            <section class="card-glass" id="complaint-form-wrapper" style="display:none; margin-bottom:1.25rem;">
                    <form action="<?php echo e(route('complaints.store')); ?>" method="POST" enctype="multipart/form-data" style="display:grid; gap:0.75rem; max-width:640px;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="owner_id" value="<?php echo e(auth()->user()->owner->id); ?>">
                        <input type="hidden" name="property_id" value="<?php echo e(auth()->user()->owner->property_id); ?>">
                        <div style="display:grid; gap:0.4rem;">
                            <label for="subject">Motif</label>
                            <input id="subject" name="subject" type="text" required maxlength="255" value="<?php echo e(old('subject')); ?>" placeholder="Ex. Fuite d’eau">
                            <?php $__errorArgs = ['subject'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="form-error"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div style="display:grid; gap:0.4rem;">
                            <label for="date">Date</label>
                            <input id="date" name="date" type="date" value="<?php echo e(old('date', now()->toDateString())); ?>">
                            <?php $__errorArgs = ['date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="form-error"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div style="display:grid; gap:0.4rem;">
                            <label for="category">Catégorie</label>
                            <select id="category" name="category">
                                <option value="eau" <?php echo e(old('category') === 'eau' ? 'selected' : ''); ?>>Eau</option>
                                <option value="electricite" <?php echo e(old('category') === 'electricite' ? 'selected' : ''); ?>>Électricité</option>
                                <option value="ascenseur" <?php echo e(old('category') === 'ascenseur' ? 'selected' : ''); ?>>Ascenseur</option>
                                <option value="autre" <?php echo e(old('category', 'autre') === 'autre' ? 'selected' : ''); ?>>Autre</option>
                            </select>
                            <?php $__errorArgs = ['category'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="form-error"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div style="display:grid; gap:0.4rem;">
                            <label for="priority">Priorité</label>
                            <select id="priority" name="priority">
                                <option value="faible" <?php echo e(old('priority') === 'faible' ? 'selected' : ''); ?>>Faible</option>
                                <option value="normale" <?php echo e(old('priority', 'normale') === 'normale' ? 'selected' : ''); ?>>Normale</option>
                                <option value="elevee" <?php echo e(old('priority') === 'elevee' ? 'selected' : ''); ?>>Élevée</option>
                            </select>
                            <?php $__errorArgs = ['priority'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="form-error"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div style="display:grid; gap:0.4rem;">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3"><?php echo e(old('description')); ?></textarea>
                            <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="form-error"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div style="display:grid; gap:0.4rem;">
                            <label for="fichier_joint">Pièce jointe (jpg, png, pdf, doc, docx — 5 Mo max)</label>
                            <input id="fichier_joint" name="fichier_joint" type="file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                            <?php $__errorArgs = ['fichier_joint'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="form-error"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <button type="submit" class="btn-primary" style="justify-self:start;">Valider</button>
                    </form>
            </section>
        <?php endif; ?>

        <section class="card-glass rc-table-card">
            <?php if($complaints->isEmpty()): ?>
                <div class="dropdown-empty">Aucune réclamation pour cette propriété.</div>
            <?php else: ?>
                <div class="rc-table-scroll">
                    <table class="rc-table">
                        <thead>
                            <tr>
                                <th>Motif</th>
                                <th>Catégorie</th>
                                <th>Date</th>
                                <?php if(auth()->user()->role === 'syndic'): ?>
                                    <th>Copropriétaire</th>
                                <?php endif; ?>
                                <th>Statut</th>
                                <th>Priorité</th>
                                <th>Pièce jointe</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $complaints; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td>
                                        <div class="rc-subject"><?php echo e($c->subject); ?></div>
                                        <?php if($c->description): ?>
                                            <div class="rc-desc"><?php echo e($c->description); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo e($c->categoryLabel()); ?></td>
                                    <td><?php echo e($c->date?->format('d/m/Y') ?? '—'); ?></td>
                                    <?php if(auth()->user()->role === 'syndic'): ?>
                                        <td><?php echo e($c->owner?->user?->name ?? $c->owner?->user?->email ?? 'Copropriétaire'); ?></td>
                                    <?php endif; ?>
                                    <td>
                                        <div class="rc-col">
                                            <span class="feature-badge rc-badge"><?php echo e($c->statusLabel()); ?></span>

                                            <?php if(auth()->user()->role === 'syndic'): ?>
                                                <form action="<?php echo e(route('complaints.status', $c)); ?>" method="POST" class="rc-row">
                                                    <?php echo csrf_field(); ?>
                                                    <select name="status" class="rc-control rc-select">
                                                        <option value="nouvelle" <?php echo e($c->status === 'nouvelle' ? 'selected' : ''); ?>>Nouvelle</option>
                                                        <option value="en_cours" <?php echo e($c->status === 'en_cours' ? 'selected' : ''); ?>>En cours</option>
                                                        <option value="finie" <?php echo e($c->status === 'finie' ? 'selected' : ''); ?>>Terminée</option>
                                                        <option value="annulee" <?php echo e($c->status === 'annulee' ? 'selected' : ''); ?>>Annulée</option>
                                                    </select>
                                                    <button type="submit" class="rc-control rc-btn-primary">Mettre à jour</button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if(auth()->user()->role === 'copropriétaire'): ?>
                                                <?php if($c->canBeValidatedByOwner()): ?>
                                                    <form action="<?php echo e(route('complaints.validate', $c)); ?>" method="POST" class="rc-row">
                                                        <?php echo csrf_field(); ?>
                                                        <button type="submit" class="rc-control rc-btn-primary">✔ Valider</button>
                                                    </form>
                                                <?php elseif($c->isValidatedByOwner()): ?>
                                                    <span class="rc-muted">Validée le <?php echo e($c->validated_at->format('d/m/Y')); ?></span>
                                                <?php else: ?>
                                                    <span class="rc-muted">—</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo e($c->priorityLabel()); ?></td>
                                    <td>
                                        <div class="rc-row">
                                            <?php if($c->fichier_joint): ?>
                                                <a href="<?php echo e(route('complaints.attachment', $c)); ?>" download class="rc-control rc-btn-secondary">Voir</a>
                                            <?php else: ?>
                                                <span class="rc-muted">Aucune</span>
                                            <?php endif; ?>

                                            <?php if(auth()->user()->role === 'copropriétaire'): ?>
                                                <form action="<?php echo e(route('complaints.upload', $c)); ?>" method="POST" enctype="multipart/form-data" class="rc-row">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="file" name="fichier_joint" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" class="rc-file">
                                                    <button type="submit" class="rc-control rc-btn-secondary"><?php echo e($c->fichier_joint ? 'Remplacer' : 'Joindre'); ?></button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <script>
            const toggleBtn = document.getElementById('toggle-complaint-form');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function () {
                    const wrapper = document.getElementById('complaint-form-wrapper');
                    const isHidden = wrapper.style.display === 'none' || wrapper.style.display === '';
                    wrapper.style.display = isHidden ? 'block' : 'none';
                    toggleBtn.textContent = isHidden ? '−' : '+';
                });
            }
        </script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Hp\Desktop\Project-syndic\nouveau-projet\resources\views/complaints/index.blade.php ENDPATH**/ ?>