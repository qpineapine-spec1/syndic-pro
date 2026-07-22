<?php
    $__user = auth()->user();
    $messages = collect();
    $totalUnread = 0;
    if ($__user) {
        $__unread = \App\Models\Message::unreadForUser($__user)->orderByDesc('created_at')->get();
        $totalUnread = $__unread->count();
        $messages = $__unread->take(5)->map(function ($m) {
            return [
                'subject' => $m->subject ?: 'Nouveau message',
                'body' => $m->body,
                'time' => $m->created_at->diffForHumans(),
                'read' => false,
            ];
        });
    }
?>
<div class="header-dropdown-wrap" x-data="{ open: false }" @click.outside="open = false">
    <a href="<?php echo e(route('messages.index')); ?>" class="icon-button" aria-label="Messages" @click.prevent="open = !open">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 5h16a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H8l-4 3V6a1 1 0 0 1 1-1Z"/></svg>
        <?php if($totalUnread > 0): ?>
            <span class="icon-badge" aria-label="<?php echo e($totalUnread); ?> nouveaux messages"><?php echo e($totalUnread > 9 ? '9+' : $totalUnread); ?></span>
        <?php endif; ?>
    </a>
    <div x-show="open" x-transition class="dropdown-panel" style="display:none;">
        <?php if($messages->isEmpty()): ?>
            <div class="dropdown-empty">Aucun message pour le moment</div>
        <?php else: ?>
            <ul>
                <?php $__currentLoopData = $messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li class="dropdown-item unread">
                        <span class="dot"></span>
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight:600;"><?php echo e($item['subject']); ?></div>
                            <div class="meta" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo e($item['body']); ?></div>
                            <div class="meta"><?php echo e($item['time']); ?></div>
                        </div>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        <?php endif; ?>
        <div class="dropdown-footer">
            <a href="<?php echo e(route('messages.index')); ?>" class="btn-secondary" style="width:100%;">Voir tout</a>
        </div>
    </div>
</div><?php /**PATH C:\Users\Hp\Desktop\Project-syndic\nouveau-projet\resources\views/components/message-dropdown.blade.php ENDPATH**/ ?>