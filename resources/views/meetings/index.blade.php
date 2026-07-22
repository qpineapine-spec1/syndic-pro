@extends('layouts.app')

@section('title', 'Réunions')

@section('content')
    <section class="page-header">
        <div class="page-header__content">
            <div class="feature-badge">Réunions</div>
            <h1 class="page-title">Planifier une réunion</h1>
        </div>
        @if(auth()->check() && auth()->user()->role === 'syndic')
            <button type="button" class="btn-secondary" onclick="document.getElementById('meeting-create-form').style.display = document.getElementById('meeting-create-form').style.display === 'block' ? 'none' : 'block'">Nouvelle réunion</button>
        @elseif(auth()->check() && auth()->user()->role === 'copropriétaire')
            <button type="button" class="btn-secondary" onclick="document.getElementById('meeting-request-wrapper').style.display = document.getElementById('meeting-request-wrapper').style.display === 'block' ? 'none' : 'block'">+ Demander une réunion</button>
        @endif
    </section>

    @if(session('status'))
        <div class="dashboard-card" style="margin-bottom:1.25rem;">{{ session('status') }}</div>
    @endif

    @if(auth()->check() && auth()->user()->role === 'syndic')
        <article class="dashboard-card" style="margin-bottom:1.25rem;">
            <form id="meeting-create-form" action="{{ route('meetings.store') }}" method="POST" style="display:grid; gap:0.9rem; margin:0;">
                @csrf
                <input type="hidden" name="property_id" value="{{ auth()->user()->syndic?->property_id }}">
                <div style="display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:0.9rem;">
                    <div style="display:grid; gap:0.35rem;">
                        <label for="type_reunion">Type de réunion</label>
                        <select id="type_reunion" name="type_reunion" class="form-control">
                            <option value="assemblee_generale">Assemblée Générale</option>
                            <option value="reunion_conseil">Réunion de conseil</option>
                            <option value="reunion_extraordinaire">Réunion extraordinaire</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    <div style="display:grid; gap:0.35rem;">
                        <label for="title">Titre</label>
                        <input id="title" type="text" name="title" required class="form-control">
                    </div>
                    <div style="display:grid; gap:0.35rem;">
                        <label for="meeting_date">Date et heure</label>
                        <input id="meeting_date" type="datetime-local" name="meeting_date" required class="form-control">
                    </div>
                    <div style="display:grid; gap:0.35rem;">
                        <label for="lieu">Lieu</label>
                        <input id="lieu" type="text" name="lieu" class="form-control">
                    </div>
                    <div style="display:grid; gap:0.35rem; grid-column:1 / -1;">
                        <label for="agenda">Ordre du jour</label>
                        <textarea id="agenda" name="agenda" class="form-control" rows="4"></textarea>
                    </div>
                    <div style="display:flex; align-items:center; gap:0.75rem; grid-column:1 / -1;">
                        <label style="display:flex; align-items:center; gap:0.55rem; margin:0;">
                            <input type="checkbox" name="notify_owners" value="1">
                            Notifier les propriétaires
                        </label>
                    </div>
                </div>
                <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
                    <button type="submit" class="btn-primary">Créer la réunion</button>
                    <button type="button" class="btn-secondary" onclick="document.getElementById('meeting-create-form').style.display='none'">Annuler</button>
                </div>
            </form>
        </article>
    @endif

    @if(auth()->check() && auth()->user()->role === 'copropriétaire')
        <article class="dashboard-card" id="meeting-request-wrapper" style="display:none; margin-bottom:1.25rem;">
            <form id="meeting-request-form" action="{{ route('meeting-requests.store') }}" method="POST" style="display:grid; gap:0.9rem; margin:0;">
                @csrf
                <input type="hidden" name="property_id" value="{{ auth()->user()->owner?->property_id }}">
                <div style="display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:0.9rem;">
                    <div style="display:grid; gap:0.35rem;">
                        <label for="req_type_reunion">Type de réunion</label>
                        <select id="req_type_reunion" name="type_reunion" class="form-control" required>
                            <option value="assemblee_generale">Assemblée Générale</option>
                            <option value="reunion_conseil">Réunion de conseil</option>
                            <option value="reunion_extraordinaire">Réunion extraordinaire</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    <div style="display:grid; gap:0.35rem;">
                        <label for="req_title">Titre / objet de la demande</label>
                        <input id="req_title" type="text" name="title" required class="form-control">
                    </div>
                    <div style="display:grid; gap:0.35rem; grid-column:1 / -1;">
                        <label for="req_motif">Motif</label>
                        <textarea id="req_motif" name="motif" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <p style="margin:0; color:var(--color-text-muted); font-size:0.9rem;">Votre demande sera soumise au vote de tous les copropriétaires. Si elle recueille plus d'1/3 des voix favorables, la réunion sera automatiquement programmée.</p>
                <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
                    <button type="submit" class="btn-primary">Soumettre la demande</button>
                    <button type="button" class="btn-secondary" onclick="document.getElementById('meeting-request-wrapper').style.display='none'">Annuler</button>
                </div>
            </form>
        </article>

        @if($meetingRequests->isNotEmpty())
            <article class="dashboard-card" style="margin-bottom:1.25rem;">
                <h2 style="margin:0 0 0.75rem;">Demandes de réunion en cours</h2>
                <div style="display:grid; gap:0.75rem;">
                    @foreach($meetingRequests as $r)
                        <div style="border:1px solid var(--color-border, #e2e2e2); border-radius:0.6rem; padding:0.75rem;">
                            <div class="feature-badge">{{ $r->type_reunion ?? 'Réunion' }}</div>
                            <h3 style="margin:0.3rem 0 0.2rem;">{{ $r->title }}</h3>
                            @if($r->description)
                                <p style="margin:0 0 0.4rem; color:var(--color-text-muted);">{{ $r->description }}</p>
                            @endif
                            <p style="margin:0 0 0.5rem;">Votes favorables : {{ $r->vote_count }} / seuil requis : {{ $r->required_threshold }}</p>
                            @if($votedRequestIds->contains($r->id))
                                <span class="btn-secondary" style="opacity:0.7;">Vous avez déjà voté</span>
                            @else
                                <form action="{{ route('meeting-requests.vote', $r) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn-primary">Voter pour cette réunion</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </article>
        @endif
    @endif

    @if($meetings->isEmpty())
        <article class="dashboard-card">
            <p style="margin:0; color:var(--color-text-muted);">Aucune réunion.</p>
        </article>
    @else
        <div class="dashboard-grid" style="grid-template-columns:repeat(1, minmax(0, 1fr)); width:100%; margin:0;">
            @foreach($meetings as $m)
                <article class="dashboard-card">
                    <div class="feature-badge">{{ $m->type_reunion ? $m->type_reunion : 'Réunion' }}</div>
                    <h2 style="margin:0.3rem 0 0.2rem;">{{ $m->title }}</h2>
                    <p style="margin:0; color:var(--color-text-muted);">{{ $m->meeting_date }}</p>
                    <a href="{{ route('meetings.show', $m) }}" class="btn-secondary" style="margin-top:auto; width:fit-content;">Voir la réunion</a>
                </article>
            @endforeach
        </div>
    @endif
@endsection