

<?php $__env->startSection('title', 'Réunions'); ?>

<?php $__env->startSection('content'); ?>
    <section class="page-header">
        <div class="page-header__content">
            <div class="feature-badge">Réunions</div>
            <h1 class="page-title">Planifier une réunion</h1>
        </div>
        <?php if(auth()->check() && auth()->user()->role === 'syndic'): ?>
            <button type="button" class="btn-secondary" onclick="document.getElementById('meeting-create-form').style.display = document.getElementById('meeting-create-form').style.display === 'block' ? 'none' : 'block'">Nouvelle réunion</button>
        <?php elseif(auth()->check() && auth()->user()->role === 'copropriétaire'): ?>
            <button type="button" class="btn-secondary" onclick="document.getElementById('meeting-request-wrapper').style.display = document.getElementById('meeting-request-wrapper').style.display === 'block' ? 'none' : 'block'">+ Demander une réunion</button>
        <?php endif; ?>
    </section>

    <?php if(session('status')): ?>
        <div class="dashboard-card" style="margin-bottom:1.25rem;"><?php echo e(session('status')); ?></div>
    <?php endif; ?>

    <?php if(auth()->check() && auth()->user()->role === 'syndic'): ?>
        <article class="dashboard-card" style="margin-bottom:1.25rem;">
            <form id="meeting-create-form" action="<?php echo e(route('meetings.store')); ?>" method="POST" style="display:grid; gap:0.9rem; margin:0;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="property_id" value="<?php echo e(auth()->user()->syndic?->property_id); ?>">
                <div style="display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:0.9rem;">
                    <div style="display:grid; gap:0.35rem;">
                        <label for="type_reunion">Type de réunion</label>
                        <select id="type_reunion" name="type_reunion" class="form-control">
                            <option value="assemblee_generale">Assemblée Générale</option>
                            <option value="reunion_conseil">Réunion de conseil</option>
                            <option value="reunion_extraordinaire">Réunion extraordinaire</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    <div style="display:grid; gap:0.35rem;">
                        <label for="title">Titre</label>
                        <input id="title" type="text" name="title" required class="form-control">
                    </div>
                    <div style="display:grid; gap:0.35rem;">
                        <label for="meeting_date">Date et heure</label>
                        <input id="meeting_date" type="datetime-local" name="meeting_date" required class="form-control">
                    </div>
                    <div style="display:grid; gap:0.35rem;">
                        <label for="lieu">Lieu</label>
                        <input id="lieu" type="text" name="lieu" class="form-control">
                    </div>
                    <div style="display:grid; gap:0.35rem; grid-column:1 / -1;">
                        <label for="agenda">Ordre du jour</label>
                        <textarea id="agenda" name="agenda" class="form-control" rows="4"></textarea>
                    </div>
                    <div style="display:flex; align-items:center; gap:0.75rem; grid-column:1 / -1;">
                        <label style="display:flex; align-items:center; gap:0.55rem; margin:0;">
                            <input type="checkbox" name="notify_owners" value="1">
                            Notifier les propriétaires
                        </label>
                    </div>
                </div>
                <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
                    <button type="submit" class="btn-primary">Créer la réunion</button>
                    <button type="button" class="btn-secondary" onclick="document.getElementById('meeting-create-form').style.display='none'">Annuler</button>
                </div>
            </form>
        </article>
    <?php endif; ?>

    <?php if(auth()->check() && auth()->user()->role === 'copropriétaire'): ?>
        <article class="dashboard-card" id="meeting-request-wrapper" style="display:none; margin-bottom:1.25rem;">
            <form id="meeting-request-form" action="<?php echo e(route('meeting-requests.store')); ?>" method="POST" style="display:grid; gap:0.9rem; margin:0;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="property_id" value="<?php echo e(auth()->user()->owner?->property_id); ?>">
                <div style="display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:0.9rem;">
                    <div style="display:grid; gap:0.35rem;">
                        <label for="req_type_reunion">Type de réunion</label>
                        <select id="req_type_reunion" name="type_reunion" class="form-control" required>
                            <option value="assemblee_generale">Assemblée Générale</option>
                            <option value="reunion_conseil">Réunion de conseil</option>
                            <option value="reunion_extraordinaire">Réunion extraordinaire</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    <div style="display:grid; gap:0.35rem;">
                        <label for="req_title">Titre / objet de la demande</label>
                        <input id="req_title" type="text" name="title" required class="form-control">
                    </div>
                    <div style="display:grid; gap:0.35rem; grid-column:1 / -1;">
                        <label for="req_motif">Motif</label>
                        <textarea id="req_motif" name="motif" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <p style="margin:0; color:var(--color-text-muted); font-size:0.9rem;">Votre demande sera soumise au vote de tous les copropriétaires. Si elle recueille plus d'1/3 des voix favorables, la réunion sera automatiquement programmée.</p>
                <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
                    <button type="submit" class="btn-primary">Soumettre la demande</button>
                    <button type="button" class="btn-secondary" onclick="document.getElementById('meeting-request-wrapper').style.display='none'">Annuler</button>
                </div>
            </form>
        </article>

        <?php if($meetingRequests->isNotEmpty()): ?>
            <article class="dashboard-card" style="margin-bottom:1.25rem;">
                <h2 style="margin:0 0 0.75rem;">Demandes de réunion en cours</h2>
                <div style="display:grid; gap:0.75rem;">
                    <?php $__currentLoopData = $meetingRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div style="border:1px solid var(--color-border, #e2e2e2); border-radius:0.6rem; padding:0.75rem;">
                            <div class="feature-badge"><?php echo e($r->type_reunion ?? 'Réunion'); ?></div>
                            <h3 style="margin:0.3rem 0 0.2rem;"><?php echo e($r->title); ?></h3>
                            <?php if($r->description): ?>
                                <p style="margin:0 0 0.4rem; color:var(--color-text-muted);"><?php echo e($r->description); ?></p>
                            <?php endif; ?>
                            <p style="margin:0 0 0.5rem;">Votes favorables : <?php echo e($r->vote_count); ?> / seuil requis : <?php echo e($r->required_threshold); ?></p>
                            <?php if($votedRequestIds->contains($r->id)): ?>
                                <span class="btn-secondary" style="opacity:0.7;">Vous avez déjà voté</span>
                            <?php else: ?>
                                <form action="<?php echo e(route('meeting-requests.vote', $r)); ?>" method="POST" style="display:inline;">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn-primary">Voter pour cette réunion</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </article>
        <?php endif; ?>
    <?php endif; ?>

    <?php if($meetings->isEmpty()): ?>
        <article class="dashboard-card">
            <p style="margin:0; color:var(--color-text-muted);">Aucune réunion.</p>
        </article>
    <?php else: ?>
        <div class="dashboard-grid" style="grid-template-columns:repeat(1, minmax(0, 1fr)); width:100%; margin:0;">
            <?php $__currentLoopData = $meetings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <article class="dashboard-card">
                    <div class="feature-badge"><?php echo e($m->type_reunion ? $m->type_reunion : 'Réunion'); ?></div>
                    <h2 style="margin:0.3rem 0 0.2rem;"><?php echo e($m->title); ?></h2>
                    <p style="margin:0; color:var(--color-text-muted);"><?php echo e($m->meeting_date); ?></p>
                    <a href="<?php echo e(route('meetings.show', $m)); ?>" class="btn-secondary" style="margin-top:auto; width:fit-content;">Voir la réunion</a>
                </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Hp\Desktop\Project-syndic\nouveau-projet\resources\views/meetings/index.blade.php ENDPATH**/ ?>