<?php
    $__user = auth()->user();
    $notifications = collect();
    $totalUnread = 0;
    if ($__user) {
        $__unread = \App\Models\Notification::forUser($__user)->where('is_read', false)->orderByDesc('created_at')->get();
        $totalUnread = $__unread->count();
        $notifications = $__unread->take(5)->map(function ($n) {
            return [
                'title' => $n->title,
                'message' => $n->message,
                'time' => $n->created_at->diffForHumans(),
                'read' => false,
            ];
        });
    }
?>
<div class="header-dropdown-wrap" x-data="{ open: false }" @click.outside="open = false">
    <a href="<?php echo e(route('notifications.index')); ?>" class="icon-button" aria-label="Notifications" @click.prevent="open = !open">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M15 17H3l1.5-3.5A7.5 7.5 0 0 1 4 8a8 8 0 1 1 16 0a7.5 7.5 0 0 1-0.5 5.5L18 17h-3"/><path d="M10 19a2 2 0 0 0 4 0"/></svg>
        <?php if($totalUnread > 0): ?>
            <span class="icon-badge" aria-label="<?php echo e($totalUnread); ?> nouvelles notifications"><?php echo e($totalUnread > 9 ? '9+' : $totalUnread); ?></span>
        <?php endif; ?>
    </a>
    <div x-show="open" x-transition class="dropdown-panel" style="display:none;">
        <?php if($notifications->isEmpty()): ?>
            <div class="dropdown-empty">Aucune notification pour le moment</div>
        <?php else: ?>
            <ul>
                <?php $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li class="dropdown-item unread">
                        <span class="dot"></span>
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight:600;"><?php echo e($item['title']); ?></div>
                            <div class="meta" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo e($item['message']); ?></div>
                            <div class="meta"><?php echo e($item['time']); ?></div>
                        </div>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        <?php endif; ?>
        <div class="dropdown-footer">
            <a href="<?php echo e(route('notifications.index')); ?>" class="btn-secondary" style="width:100%;">Voir tout (marque tout comme lu)</a>
        </div>
    </div>
</div>
</div><?php /**PATH C:\Users\Hp\Desktop\Project-syndic\nouveau-projet\resources\views/components/notification-dropdown.blade.php ENDPATH**/ ?>