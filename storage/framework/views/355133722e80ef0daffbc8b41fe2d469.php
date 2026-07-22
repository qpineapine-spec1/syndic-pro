

<?php $__env->startSection('title', 'Réunion'); ?>

<?php $__env->startSection('content'); ?>
    <h1><?php echo e($meeting->title); ?></h1>
    <div><strong>Type :</strong> <?php echo e($meeting->type_reunion ?? 'assemblee_generale'); ?></div>
    <div><strong>Date :</strong> <?php echo e($meeting->meeting_date); ?></div>
    <div><strong>Lieu :</strong> <?php echo e($meeting->lieu ?? 'À définir'); ?></div>
    <div><strong>Ordre du jour :</strong> <?php echo e($meeting->agenda); ?></div>
    <div><strong>Statut :</strong> <?php echo e($meeting->status); ?></div>

    <?php if(auth()->check() && auth()->user()->role === 'syndic' && auth()->user()->syndic?->property_id === $meeting->property_id): ?>
        <div style="margin-top:1rem;">
            <form action="<?php echo e(route('meetings.update', $meeting)); ?>" method="POST" style="margin-bottom:1rem; padding:1rem; border:1px solid #ddd; border-radius:12px; background:#fff;">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>
                <input type="hidden" name="property_id" value="<?php echo e($meeting->property_id); ?>">
                <div style="display:grid;gap:0.75rem;">
                    <div><label>Titre</label><input type="text" name="title" value="<?php echo e($meeting->title); ?>" class="form-control"></div>
                    <div><label>Type de réunion</label><select name="type_reunion" class="form-control"><option value="assemblee_generale" <?php echo e($meeting->type_reunion === 'assemblee_generale' ? 'selected' : ''); ?>>Assemblée Générale</option><option value="reunion_conseil" <?php echo e($meeting->type_reunion === 'reunion_conseil' ? 'selected' : ''); ?>>Réunion de conseil</option><option value="reunion_extraordinaire" <?php echo e($meeting->type_reunion === 'reunion_extraordinaire' ? 'selected' : ''); ?>>Réunion extraordinaire</option><option value="autre" <?php echo e($meeting->type_reunion === 'autre' ? 'selected' : ''); ?>>Autre</option></select></div>
                    <div><label>Date</label><input type="datetime-local" name="meeting_date" value="<?php echo e(\Illuminate\Support\Str::replace(' ', 'T', $meeting->meeting_date)); ?>" class="form-control"></div>
                    <div><label>Ordre du jour</label><textarea name="agenda" class="form-control"><?php echo e($meeting->agenda); ?></textarea></div>
                    <div><label>Lieu</label><input type="text" name="lieu" value="<?php echo e($meeting->lieu); ?>" class="form-control"></div>
                    <button type="submit" class="btn-primary">Enregistrer</button>
                </div>
            </form>

            <form action="<?php echo e(route('meetings.cancel', $meeting)); ?>" method="POST" style="margin-bottom:1rem;">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn-secondary">Annuler la réunion</button>
            </form>

            <div style="margin-top:1rem;">
                <a href="<?php echo e(route('meetings.report.template', $meeting)); ?>" class="btn-secondary">Télécharger le modèle de compte-rendu</a>
            </div>
            <form action="<?php echo e(route('meetings.report.upload', $meeting)); ?>" method="POST" enctype="multipart/form-data" style="margin-top:1rem;">
                <?php echo csrf_field(); ?>
                <label for="report">Uploader le compte-rendu rempli</label>
                <input type="file" name="report" id="report" />
                <button type="submit" class="btn-primary">Uploader</button>
            </form>
        <?php endif; ?>

        <?php if($meeting->compte_rendu): ?>
            <div style="margin-top:1rem;"><a href="<?php echo e(route('meetings.report.download', $meeting)); ?>" class="btn-secondary">Télécharger le compte-rendu</a></div>
        <?php endif; ?>

        <h2 style="margin-top:2rem;">Votes</h2>
        <?php if(auth()->check() && auth()->user()->role === 'syndic' && auth()->user()->syndic?->property_id === $meeting->property_id): ?>
            <form action="<?php echo e(route('votes.store')); ?>" method="POST" style="margin-bottom:1rem; padding:1rem; border:1px solid #ddd; border-radius:12px; background:#fff;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="meeting_id" value="<?php echo e($meeting->id); ?>">
                <div style="display:grid;gap:0.75rem;">
                    <div><label>Question</label><input type="text" name="question" required class="form-control"></div>
                    <div><label>Choix (un par ligne)</label><textarea name="choices" required class="form-control">Oui
Non
Abstention</textarea></div>
                    <div><label>Type de vote</label><select name="vote_type" class="form-control" onchange="document.getElementById('vote-max-wrapper').style.display = this.value === 'multiple' ? 'block' : 'none'">
                        <option value="single">Choix unique</option>
                        <option value="multiple">Choix multiple</option>
                    </select></div>
                    <div id="vote-max-wrapper" style="display:none;"><label>Nombre de choix maximum autorisés par électeur</label><input type="number" name="nb_choix_autorises" value="2" min="2" class="form-control"></div>
                    <div><label>Date de début</label><input type="datetime-local" name="starts_at" required class="form-control"></div>
                    <div><label>Date de fin</label><input type="datetime-local" name="ends_at" required class="form-control"></div>
                    <button type="submit" class="btn-primary">Créer le vote</button>
                </div>
            </form>
        <?php endif; ?>

        <?php $__currentLoopData = $meeting->votes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vote): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div style="margin-top:1rem; padding:1rem; border:1px solid #ddd; border-radius:12px; background:#fff;">
                <h3><?php echo e($vote->question); ?></h3>
                <p><strong>Statut :</strong> <?php echo e($vote->status); ?> | <strong>Début :</strong> <?php echo e($vote->starts_at); ?> | <strong>Fin :</strong> <?php echo e($vote->ends_at); ?></p>
                <?php if(auth()->check() && auth()->user()->role === 'syndic' && auth()->user()->syndic?->property_id === $meeting->property_id && $vote->status !== 'closed'): ?>
                    <form action="<?php echo e(route('votes.close', $vote)); ?>" method="POST" style="margin-bottom:0.75rem;">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn-secondary">Clôturer le vote</button>
                    </form>
                <?php endif; ?>
                <?php if(auth()->check() && auth()->user()->role === 'copropriétaire' && $vote->status === 'open'): ?>
                    <form action="<?php echo e(route('votes.participate', $vote)); ?>" method="POST" style="margin-bottom:0.75rem;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="vote_choice_ids[]" value="">
                        <?php $__currentLoopData = $vote->voteChoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $choice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <label style="display:block; margin:0.25rem 0;"><input type="checkbox" name="vote_choice_ids[]" value="<?php echo e($choice->id); ?>"> <?php echo e($choice->label); ?></label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <button type="submit" class="btn-primary">Voter</button>
                    </form>
                <?php endif; ?>
                <ul>
                    <?php $__currentLoopData = $vote->voteChoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $choice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php $count = $choice->voteParticipations->count(); ?>
                        <li><?php echo e($choice->label); ?> : <?php echo e($count); ?> vote<?php echo e($count > 1 ? 's' : ''); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
                <?php if($vote->status === 'closed' && $vote->final_decision): ?>
                    <p><strong>Décision finale :</strong> <?php echo e($vote->final_decision); ?></p>
                <?php endif; ?>
                <?php if(auth()->check() && auth()->user()->role === 'syndic' && auth()->user()->syndic?->property_id === $meeting->property_id): ?>
                    <?php $participants = $vote->voteChoices->flatMap(fn($choice) => $choice->voteParticipations->map(fn($p) => $p->owner->office_number ?? 'N/A')); ?>
                    <p><strong>Participants :</strong> <?php echo e($participants->implode(', ')); ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Hp\Desktop\Project-syndic\nouveau-projet\resources\views/meetings/show.blade.php ENDPATH**/ ?>