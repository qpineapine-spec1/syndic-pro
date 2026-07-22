

<?php $__env->startSection('title', 'Mes informations personnelles'); ?>

<?php $__env->startSection('content'); ?>
        <section class="page-header">
            <div>
                <div class="feature-badge">Profil copropriétaire</div>
                <h1 class="page-title" style="margin-top:0.7rem;">Mes informations personnelles</h1>
            </div>
        </section>

        <?php echo $__env->make('partials.flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <section class="card-glass" style="max-width:600px; margin:2rem auto; padding:2rem;">
            <form method="POST" action="<?php echo e(route('profile.update')); ?>" class="form-grid">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PATCH'); ?>

                <div class="form-field">
                    <label for="email">Email (non modifiable)</label>
                    <input id="email" type="email" name="email" value="<?php echo e($user->email); ?>" readonly>
                </div>

                <div class="form-field">
                    <label for="name">Nom complet</label>
                    <input id="name" type="text" name="name" value="<?php echo e(old('name', $user->name)); ?>" required>
                    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="form-error"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="form-field">
                    <label for="lot_surface">Surface du lot (m²)</label>
                    <input id="lot_surface" type="number" name="lot_surface" value="<?php echo e(old('lot_surface', $owner->lot_surface)); ?>" step="0.01" required>
                    <?php $__errorArgs = ['lot_surface'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="form-error"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="form-field">
                    <label for="surface_confirmation">Confirmation de surface (m²)</label>
                    <input id="surface_confirmation" type="number" name="surface_confirmation" value="<?php echo e(old('surface_confirmation', $owner->surface_confirmation)); ?>" step="0.01" required>
                    <?php $__errorArgs = ['surface_confirmation'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="form-error"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="form-field">
                    <label for="has_mezzanine">Mezzanine ?</label>
                    <select id="has_mezzanine" name="has_mezzanine" required>
                        <option value="0" <?php echo e(old('has_mezzanine', $owner->has_mezzanine ? '1' : '0') === '0' ? 'selected' : ''); ?>>Non</option>
                        <option value="1" <?php echo e(old('has_mezzanine', $owner->has_mezzanine ? '1' : '0') === '1' ? 'selected' : ''); ?>>Oui</option>
                    </select>
                    <?php $__errorArgs = ['has_mezzanine'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="form-error"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="form-field" id="mezzanine_surface_wrapper" style="display:<?php echo e(old('has_mezzanine', $owner->has_mezzanine ? '1' : '0') === '1' ? 'block' : 'none'); ?>;">
                    <label for="mezzanine_surface">Surface mezzanine (m²)</label>
                    <input id="mezzanine_surface" type="number" name="mezzanine_surface" value="<?php echo e(old('mezzanine_surface', $owner->mezzanine_surface)); ?>" step="0.01">
                    <?php $__errorArgs = ['mezzanine_surface'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="form-error"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="form-field">
                    <label for="office_number">Numéro de bureau</label>
                    <input id="office_number" type="text" name="office_number" value="<?php echo e(old('office_number', $owner->office_number)); ?>">
                    <?php $__errorArgs = ['office_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="form-error"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="form-field">
                    <label for="floor">Étage</label>
                    <input id="floor" type="number" name="floor" value="<?php echo e(old('floor', $owner->floor)); ?>">
                    <?php $__errorArgs = ['floor'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="form-error"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="form-field">
                    <label for="telephone">Téléphone</label>
                    <input id="telephone" type="text" name="telephone" value="<?php echo e(old('telephone', $owner->telephone)); ?>" maxlength="20">
                    <?php $__errorArgs = ['telephone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="form-error"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div style="display:flex; gap:1rem; margin-top:1.5rem;">
                    <button type="submit" class="btn-primary">Enregistrer les modifications</button>
                    <a href="<?php echo e(route('dashboard')); ?>" class="btn-secondary" style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center;">Retour au dashboard</a>
                </div>
            </form>

            <script>
                document.getElementById('has_mezzanine').addEventListener('change', function() {
                    const wrapper = document.getElementById('mezzanine_surface_wrapper');
                    const input = document.getElementById('mezzanine_surface');
                    if (this.value === '1') {
                        wrapper.style.display = 'block';
                    } else {
                        wrapper.style.display = 'none';
                        input.value = '';
                    }
                });
            </script>
        </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Hp\Desktop\Project-syndic\nouveau-projet\resources\views/profile/show.blade.php ENDPATH**/ ?>