<?php if(session('status')): ?>
    <div class="alert-success" role="status">
        <?php echo e(session('status')); ?>

    </div>
<?php endif; ?>

<?php if(session('success')): ?>
    <div class="alert-success" role="status">
        <?php echo e(session('success')); ?>

    </div>
<?php endif; ?>

<?php if(session('error')): ?>
    <div class="alert-danger" role="alert">
        <?php echo e(session('error')); ?>

    </div>
<?php endif; ?>
<?php /**PATH C:\Users\Hp\Desktop\Project-syndic\nouveau-projet\resources\views/partials/flash.blade.php ENDPATH**/ ?>