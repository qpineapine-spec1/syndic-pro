@extends('layouts.app')

@section('content')
    @php
        $isSyndic = auth()->user()->role === 'syndic';
        $activeConversation = $conversations->first(function ($c) use ($activeOwnerId) {
            return (int) $c->owner_id === (int) $activeOwnerId;
        });
        $activeOwner = $activeConversation->owner ?? null;
    @endphp

    <section class="page-header">
        <div>
            <div class="feature-badge">Messagerie</div>
            <h1 class="page-title">{{ $isSyndic ? 'Conversations avec les copropriétaires' : 'Conversation avec le syndic' }}</h1>
        </div>
    </section>

    <section class="chat-app card-glass" data-active-owner="{{ $activeOwnerId }}" data-poll-url="{{ $activeOwner ? route('messages.poll', $activeOwner) : '' }}" data-older-url="{{ $activeOwner ? route('messages.older', $activeOwner) : '' }}" data-has-more="{{ ($chatHasMore ?? false) ? '1' : '0' }}" data-oldest-id="{{ $chatOldestId ?? 0 }}" data-is-syndic="{{ $isSyndic ? '1' : '0' }}">
        <aside class="chat-sidebar">
            @if($isSyndic)
                <div class="chat-sidebar-header">Copropriétaires</div>
                <form action="{{ route('messages.store') }}" method="POST" class="chat-broadcast-form">
                    @csrf
                    <input type="hidden" name="broadcast_to_all" value="1">
                    <input type="hidden" name="owner_id" value="all">
                    <input type="text" name="body" placeholder="Message à tous les copropriétaires…" required>
                    <button type="submit" class="btn-secondary" title="Envoyer à tous">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4 20-7Z"/></svg>
                        Tous
                    </button>
                </form>
            @endif

            @if($conversations->isEmpty())
                <div class="dropdown-empty">Aucune conversation disponible pour le moment.</div>
            @endif

            <ul class="chat-contact-list">
                @foreach($conversations as $conversation)
                    @php
                        $contactName = $isSyndic
                            ? ($conversation->owner->user?->name ?? 'Copropriétaire')
                            : (auth()->user()->owner?->property?->syndics()->with('user')->first()?->user?->name ?? 'Syndic');
                        $initials = collect(explode(' ', trim($contactName)))->map(fn($p) => mb_substr($p, 0, 1))->take(2)->implode('');
                        $isActive = (int) $conversation->owner_id === (int) $activeOwnerId;
                        $lastMsg = $conversation->last_message;
                    @endphp
                    <li>
                        <a href="{{ route('messages.index', ['owner' => $conversation->owner_id]) }}" class="chat-contact {{ $isActive ? 'is-active' : '' }}">
                            <span class="chat-avatar">{{ strtoupper($initials) ?: '?' }}</span>
                            <span class="chat-contact-body">
                                <span class="chat-contact-top">
                                    <span class="chat-contact-name">{{ $contactName }}</span>
                                    @if($lastMsg)
                                        <span class="chat-contact-time">{{ $lastMsg->created_at->format('H:i') }}</span>
                                    @endif
                                </span>
                                <span class="chat-contact-preview">{{ $lastMsg ? \Illuminate\Support\Str::limit($lastMsg->body, 38) : 'Aucun message pour le moment' }}</span>
                            </span>
                            @if($conversation->unread_count > 0)
                                <span class="chat-unread-badge">{{ $conversation->unread_count }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </aside>

        <div class="chat-window">
            @if($activeOwner)
                @php
                    $headerName = $isSyndic
                        ? ($activeOwner->user?->name ?? 'Copropriétaire')
                        : (auth()->user()->owner?->property?->syndics()->with('user')->first()?->user?->name ?? 'Syndic');
                @endphp
                <div class="chat-window-header">
                    <span class="chat-avatar">{{ strtoupper(collect(explode(' ', trim($headerName)))->map(fn($p) => mb_substr($p, 0, 1))->take(2)->implode('')) ?: '?' }}</span>
                    <div>
                        <div class="chat-window-name">{{ $headerName }}</div>
                        @if($isSyndic)
                            <div class="chat-window-meta">{{ $activeOwner->office_number ? 'Bureau ' . $activeOwner->office_number : 'Lot non renseigné' }}</div>
                        @endif
                    </div>
                </div>

                <div class="chat-messages" id="chat-messages">
                    @forelse($activeConversation->messages as $m)
                        @php
                            $mine = ($isSyndic && $m->sender_type === 'syndic') || (!$isSyndic && $m->sender_type === 'owner');
                        @endphp
                        <div class="chat-bubble-row {{ $mine ? 'is-mine' : 'is-theirs' }}" data-id="{{ $m->id }}">
                            <div class="chat-bubble">
                                @if($m->subject)
                                    <div class="chat-bubble-subject">{{ $m->subject }}</div>
                                @endif
                                <div class="chat-bubble-text">{{ $m->body }}</div>
                                <div class="chat-bubble-time">{{ $m->created_at->format('H:i') }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="dropdown-empty">Aucun message. Démarrez la conversation !</div>
                    @endforelse
                </div>

                <form action="{{ route('messages.store') }}" method="POST" class="chat-input-form">
                    @csrf
                    <input type="hidden" name="owner_id" value="{{ $activeOwner->id }}">
                    <input type="text" name="body" placeholder="Écrivez votre message…" autocomplete="off" required>
                    <button type="submit" class="chat-send-btn" aria-label="Envoyer">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4 20-7Z"/></svg>
                    </button>
                </form>
            @else
                <div class="chat-empty-state">
                    <p>Sélectionnez une conversation pour commencer à échanger.</p>
                </div>
            @endif
        </div>
    </section>

    @if($activeOwner)
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
    @endif
@endsection