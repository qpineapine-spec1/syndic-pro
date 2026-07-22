

<?php $__env->startSection('content'); ?>
    <section class="page-header">
        <div>
            <div class="feature-badge">Notifications</div>
            <h1 class="page-title">Toutes vos notifications</h1>
        </div>
    </section>

    <section class="card-glass" style="padding:1.5rem; display:grid; gap:0.7rem;">
        <?php if($notifications->isEmpty()): ?>
            <div class="dropdown-empty">Aucune notification pour le moment.</div>
        <?php else: ?>
            <ul style="list-style:none; padding:0; margin:0; display:grid; gap:0.6rem;">
                <?php $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li class="dropdown-item <?php echo e($item->is_read ? '' : 'unread'); ?>" style="align-items:center;">
                        <?php if(!$item->is_read): ?> <span class="dot"></span><?php endif; ?>
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight:700;"><?php echo e($item->title); ?></div>
                            <div class="meta"><?php echo e($item->message); ?></div>
                            <div class="meta"><?php echo e($item->sent_at?->format('d/m/Y H:i') ?? $item->created_at->format('d/m/Y H:i')); ?></div>
                        </div>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        <?php endif; ?>
    </section>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Hp\Desktop\Project-syndic\nouveau-projet\resources\views/notifications/index.blade.php ENDPATH**/ ?>