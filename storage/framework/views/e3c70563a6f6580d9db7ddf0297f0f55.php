

<?php $__env->startSection('content'); ?>
    <?php
        $isSyndic = auth()->user()->role === 'syndic';
        $activeConversation = $conversations->first(function ($c) use ($activeOwnerId) {
            return (int) $c->owner_id === (int) $activeOwnerId;
        });
        $activeOwner = $activeConversation->owner ?? null;
    ?>

    <section class="page-header">
        <div>
            <div class="feature-badge">Messagerie</div>
            <h1 class="page-title"><?php echo e($isSyndic ? 'Conversations avec les copropriétaires' : 'Conversation avec le syndic'); ?></h1>
        </div>
    </section>

    <section class="chat-app card-glass" data-active-owner="<?php echo e($activeOwnerId); ?>" data-poll-url="<?php echo e($activeOwner ? route('messages.poll', $activeOwner) : ''); ?>" data-older-url="<?php echo e($activeOwner ? route('messages.older', $activeOwner) : ''); ?>" data-has-more="<?php echo e(($chatHasMore ?? false) ? '1' : '0'); ?>" data-oldest-id="<?php echo e($chatOldestId ?? 0); ?>" data-is-syndic="<?php echo e($isSyndic ? '1' : '0'); ?>">
        <aside class="chat-sidebar">
            <?php if($isSyndic): ?>
                <div class="chat-sidebar-header">Copropriétaires</div>
                <form action="<?php echo e(route('messages.store')); ?>" method="POST" class="chat-broadcast-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="broadcast_to_all" value="1">
                    <input type="hidden" name="owner_id" value="all">
                    <input type="text" name="body" placeholder="Message à tous les copropriétaires…" required>
                    <button type="submit" class="btn-secondary" title="Envoyer à tous">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4 20-7Z"/></svg>
                        Tous
                    </button>
                </form>
            <?php endif; ?>

            <?php if($conversations->isEmpty()): ?>
                <div class="dropdown-empty">Aucune conversation disponible pour le moment.</div>
            <?php endif; ?>

            <ul class="chat-contact-list">
                <?php $__currentLoopData = $conversations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $conversation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $contactName = $isSyndic
                            ? ($conversation->owner->user?->name ?? 'Copropriétaire')
                            : (auth()->user()->owner?->property?->syndics()->with('user')->first()?->user?->name ?? 'Syndic');
                        $initials = collect(explode(' ', trim($contactName)))->map(fn($p) => mb_substr($p, 0, 1))->take(2)->implode('');
                        $isActive = (int) $conversation->owner_id === (int) $activeOwnerId;
                        $lastMsg = $conversation->last_message;
                    ?>
                    <li>
                        <a href="<?php echo e(route('messages.index', ['owner' => $conversation->owner_id])); ?>" class="chat-contact <?php echo e($isActive ? 'is-active' : ''); ?>">
                            <span class="chat-avatar"><?php echo e(strtoupper($initials) ?: '?'); ?></span>
                            <span class="chat-contact-body">
                                <span class="chat-contact-top">
                                    <span class="chat-contact-name"><?php echo e($contactName); ?></span>
                                    <?php if($lastMsg): ?>
                                        <span class="chat-contact-time"><?php echo e($lastMsg->created_at->format('H:i')); ?></span>
                                    <?php endif; ?>
                                </span>
                                <span class="chat-contact-preview"><?php echo e($lastMsg ? \Illuminate\Support\Str::limit($lastMsg->body, 38) : 'Aucun message pour le moment'); ?></span>
                            </span>
                            <?php if($conversation->unread_count > 0): ?>
                                <span class="chat-unread-badge"><?php echo e($conversation->unread_count); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </aside>

        <div class="chat-window">
            <?php if($activeOwner): ?>
                <?php
                    $headerName = $isSyndic
                        ? ($activeOwner->user?->name ?? 'Copropriétaire')
                        : (auth()->user()->owner?->property?->syndics()->with('user')->first()?->user?->name ?? 'Syndic');
                ?>
                <div class="chat-window-header">
                    <span class="chat-avatar"><?php echo e(strtoupper(collect(explode(' ', trim($headerName)))->map(fn($p) => mb_substr($p, 0, 1))->take(2)->implode('')) ?: '?'); ?></span>
                    <div>
                        <div class="chat-window-name"><?php echo e($headerName); ?></div>
                        <?php if($isSyndic): ?>
                            <div class="chat-window-meta"><?php echo e($activeOwner->office_number ? 'Bureau ' . $activeOwner->office_number : 'Lot non renseigné'); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="chat-messages" id="chat-messages">
                    <?php $__empty_1 = true; $__currentLoopData = $activeConversation->messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $mine = ($isSyndic && $m->sender_type === 'syndic') || (!$isSyndic && $m->sender_type === 'owner');
                        ?>
                        <div class="chat-bubble-row <?php echo e($mine ? 'is-mine' : 'is-theirs'); ?>" data-id="<?php echo e($m->id); ?>">
                            <div class="chat-bubble">
                                <?php if($m->subject): ?>
                                    <div class="chat-bubble-subject"><?php echo e($m->subject); ?></div>
                                <?php endif; ?>
                                <div class="chat-bubble-text"><?php echo e($m->body); ?></div>
                                <div class="chat-bubble-time"><?php echo e($m->created_at->format('H:i')); ?></div>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="dropdown-empty">Aucun message. Démarrez la conversation !</div>
                    <?php endif; ?>
                </div>

                <form action="<?php echo e(route('messages.store')); ?>" method="POST" class="chat-input-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="owner_id" value="<?php echo e($activeOwner->id); ?>">
                    <input type="text" name="body" placeholder="Écrivez votre message…" autocomplete="off" required>
                    <button type="submit" class="chat-send-btn" aria-label="Envoyer">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4 20-7Z"/></svg>
                    </button>
                </form>
            <?php else: ?>
                <div class="chat-empty-state">
                    <p>Sélectionnez une conversation pour commencer à échanger.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php if($activeOwner): ?>
    <script>
        (function () {
            const messagesEl = document.getElementById('chat-messages');
            const chatApp = document.querySelector('.chat-app');
            if (!messagesEl || !chatApp) return;

            messagesEl.scrollTop = messagesEl.scrollHeight;

            const pollUrl = chatApp.dataset.pollUrl;
            const olderUrl = chatApp.dataset.olderUrl;
            let hasMore = chatApp.dataset.hasMore === '1';
            let oldestId = parseInt(chatApp.dataset.oldestId || '0', 10);
            let loadingOlder = false;

            let lastId = 0;
            const rows = messagesEl.querySelectorAll('.chat-bubble-row');
            lastId = rows.length ? parseInt(rows[rows.length - 1].dataset.id || '0', 10) : 0;

            function buildRow(m) {
                const row = document.createElement('div');
                row.className = 'chat-bubble-row ' + (m.mine ? 'is-mine' : 'is-theirs');
                row.dataset.id = m.id;
                row.innerHTML = `
                    <div class="chat-bubble">
                        ${m.subject ? `<div class="chat-bubble-subject">${m.subject}</div>` : ''}
                        <div class="chat-bubble-text"></div>
                        <div class="chat-bubble-time">${m.time}</div>
                    </div>`;
                row.querySelector('.chat-bubble-text').textContent = m.body;
                return row;
            }

            function appendMessage(m) {
                messagesEl.appendChild(buildRow(m));
            }

            function poll() {
                if (!pollUrl) return;
                fetch(pollUrl + '?after_id=' + lastId, { headers: { 'Accept': 'application/json' } })
                    .then((r) => r.ok ? r.json() : null)
                    .then((data) => {
                        if (!data || !data.messages || !data.messages.length) return;
                        const wasAtBottom = messagesEl.scrollHeight - messagesEl.scrollTop - messagesEl.clientHeight < 40;
                        data.messages.forEach((m) => {
                            appendMessage(m);
                            lastId = m.id;
                        });
                        if (wasAtBottom) messagesEl.scrollTop = messagesEl.scrollHeight;
                    })
                    .catch(() => {});
            }

            // Load older history when the user scrolls near the top,
            // the same lazy-loading pattern WhatsApp uses for long threads.
            function loadOlder() {
                if (!olderUrl || !hasMore || loadingOlder || !oldestId) return;
                loadingOlder = true;

                const indicator = document.createElement('div');
                indicator.className = 'chat-messages-loading';
                indicator.textContent = 'Chargement des messages précédents…';
                messagesEl.prepend(indicator);

                const previousHeight = messagesEl.scrollHeight;
                const previousScrollTop = messagesEl.scrollTop;

                fetch(olderUrl + '?before_id=' + oldestId, { headers: { 'Accept': 'application/json' } })
                    .then((r) => r.ok ? r.json() : null)
                    .then((data) => {
                        indicator.remove();
                        if (!data || !data.messages) return;

                        hasMore = !!data.has_more;
                        if (data.messages.length) {
                            const fragment = document.createDocumentFragment();
                            data.messages.forEach((m) => fragment.appendChild(buildRow(m)));
                            messagesEl.prepend(fragment);
                            oldestId = data.messages[0].id;

                            // Keep the view anchored on the same message the
                            // user was looking at, instead of jumping.
                            messagesEl.scrollTop = previousScrollTop + (messagesEl.scrollHeight - previousHeight);
                        }
                    })
                    .catch(() => { indicator.remove(); })
                    .finally(() => { loadingOlder = false; });
            }

            messagesEl.addEventListener('scroll', () => {
                if (messagesEl.scrollTop < 80) loadOlder();
            });

            setInterval(poll, 4000);
        })();
    </script>
    <?php endif; ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Hp\Desktop\Project-syndic\nouveau-projet\resources\views/messages/index.blade.php ENDPATH**/ ?>