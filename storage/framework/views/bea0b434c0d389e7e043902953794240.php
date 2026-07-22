

<?php $__env->startSection('content'); ?>
<div class="card-glass" style="padding:2rem;">
    <h2>Importer PDF - Première Assemblée</h2>
    <form action="<?php echo e(route('import.preview')); ?>" method="post" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <div>
            <label for="pdf">Fichier PDF</label>
            <input type="file" name="pdf" id="pdf" accept="application/pdf" required />
        </div>
        <?php if(!empty($alreadyImported)): ?>
            <div class="alert alert-warning" style="margin-top:1rem;">
                <strong>Attention :</strong> Un import a déjà été effectué pour votre immeuble le <?php echo e(($importedAt?->format('d/m/Y à H:i') ?? $importedAt)); ?>. Un nouvel import peut créer des doublons.
            </div>
            <div style="margin-top:1rem;">
                <label><input type="checkbox" id="force_import" name="force" value="1"> Je comprends les risques et je veux forcer cet import</label>
            </div>
        <?php endif; ?>

        <div style="margin-top:1rem;">
            <button id="submitBtn" class="btn-primary">Télécharger et Prévisualiser</button>
        </div>
        <script>
            (function(){
                var already = !!<?php echo e(!empty($alreadyImported) ? '1' : '0'); ?>;
                var submit = document.getElementById('submitBtn');
                if (already) {
                    submit.disabled = true;
                    var chk = document.getElementById('force_import');
                    if (chk) {
                        chk.addEventListener('change', function() { submit.disabled = !this.checked; });
                    }
                }
            })();
        </script>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Hp\Desktop\Project-syndic\nouveau-projet\resources\views/import/upload.blade.php ENDPATH**/ ?>