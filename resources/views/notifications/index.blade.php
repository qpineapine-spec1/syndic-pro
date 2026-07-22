@extends('layouts.app')

@section('content')
    <section class="page-header">
        <div>
            <div class="feature-badge">Notifications</div>
            <h1 class="page-title">Toutes vos notifications</h1>
        </div>
    </section>

    <section class="card-glass" style="padding:1.5rem; display:grid; gap:0.7rem;">
        @if($notifications->isEmpty())
            <div class="dropdown-empty">Aucune notification pour le moment.</div>
        @else
            <ul style="list-style:none; padding:0; margin:0; display:grid; gap:0.6rem;">
                @foreach($notifications as $item)
                    <li class="dropdown-item {{ $item->is_read ? '' : 'unread' }}" style="align-items:center;">
                        @if(!$item->is_read) <span class="dot"></span>@endif
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight:700;">{{ $item->title }}</div>
                            <div class="meta">{{ $item->message }}</div>
                            <div class="meta">{{ $item->sent_at?->format('d/m/Y H:i') ?? $item->created_at->format('d/m/Y H:i') }}</div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection