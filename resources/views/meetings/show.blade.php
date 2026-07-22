@extends('layouts.app')

@section('title', 'Réunion')

@section('content')
    <h1>{{ $meeting->title }}</h1>
    <div><strong>Type :</strong> {{ $meeting->type_reunion ?? 'assemblee_generale' }}</div>
    <div><strong>Date :</strong> {{ $meeting->meeting_date }}</div>
    <div><strong>Lieu :</strong> {{ $meeting->lieu ?? 'À définir' }}</div>
    <div><strong>Ordre du jour :</strong> {{ $meeting->agenda }}</div>
    <div><strong>Statut :</strong> {{ $meeting->status }}</div>

    @if(auth()->check() && auth()->user()->role === 'syndic' && auth()->user()->syndic?->property_id === $meeting->property_id)
        <div style="margin-top:1rem;">
            <form action="{{ route('meetings.update', $meeting) }}" method="POST" style="margin-bottom:1rem; padding:1rem; border:1px solid #ddd; border-radius:12px; background:#fff;">
                @csrf
                @method('PUT')
                <input type="hidden" name="property_id" value="{{ $meeting->property_id }}">
                <div style="display:grid;gap:0.75rem;">
                    <div><label>Titre</label><input type="text" name="title" value="{{ $meeting->title }}" class="form-control"></div>
                    <div><label>Type de réunion</label><select name="type_reunion" class="form-control"><option value="assemblee_generale" {{ $meeting->type_reunion === 'assemblee_generale' ? 'selected' : '' }}>Assemblée Générale</option><option value="reunion_conseil" {{ $meeting->type_reunion === 'reunion_conseil' ? 'selected' : '' }}>Réunion de conseil</option><option value="reunion_extraordinaire" {{ $meeting->type_reunion === 'reunion_extraordinaire' ? 'selected' : '' }}>Réunion extraordinaire</option><option value="autre" {{ $meeting->type_reunion === 'autre' ? 'selected' : '' }}>Autre</option></select></div>
                    <div><label>Date</label><input type="datetime-local" name="meeting_date" value="{{ \Illuminate\Support\Str::replace(' ', 'T', $meeting->meeting_date) }}" class="form-control"></div>
                    <div><label>Ordre du jour</label><textarea name="agenda" class="form-control">{{ $meeting->agenda }}</textarea></div>
                    <div><label>Lieu</label><input type="text" name="lieu" value="{{ $meeting->lieu }}" class="form-control"></div>
                    <button type="submit" class="btn-primary">Enregistrer</button>
                </div>
            </form>

            <form action="{{ route('meetings.cancel', $meeting) }}" method="POST" style="margin-bottom:1rem;">
                @csrf
                <button type="submit" class="btn-secondary">Annuler la réunion</button>
            </form>

            <div style="margin-top:1rem;">
                <a href="{{ route('meetings.report.template', $meeting) }}" class="btn-secondary">Télécharger le modèle de compte-rendu</a>
            </div>
            <form action="{{ route('meetings.report.upload', $meeting) }}" method="POST" enctype="multipart/form-data" style="margin-top:1rem;">
                @csrf
                <label for="report">Uploader le compte-rendu rempli</label>
                <input type="file" name="report" id="report" />
                <button type="submit" class="btn-primary">Uploader</button>
            </form>
        @endif

        @if($meeting->compte_rendu)
            <div style="margin-top:1rem;"><a href="{{ route('meetings.report.download', $meeting) }}" class="btn-secondary">Télécharger le compte-rendu</a></div>
        @endif

        <h2 style="margin-top:2rem;">Votes</h2>
        @if(auth()->check() && auth()->user()->role === 'syndic' && auth()->user()->syndic?->property_id === $meeting->property_id)
            <form action="{{ route('votes.store') }}" method="POST" style="margin-bottom:1rem; padding:1rem; border:1px solid #ddd; border-radius:12px; background:#fff;">
                @csrf
                <input type="hidden" name="meeting_id" value="{{ $meeting->id }}">
                <div style="display:grid;gap:0.75rem;">
                    <div><label>Question</label><input type="text" name="question" required class="form-control"></div>
                    <div><label>Choix (un par ligne)</label><textarea name="choices" required class="form-control">Oui
Non
Abstention</textarea></div>
                    <div><label>Type de vote</label><select name="vote_type" class="form-control" onchange="document.getElementById('vote-max-wrapper').style.display = this.value === 'multiple' ? 'block' : 'none'">
                        <option value="single">Choix unique</option>
                        <option value="multiple">Choix multiple</option>
                    </select></div>
                    <div id="vote-max-wrapper" style="display:none;"><label>Nombre de choix maximum autorisés par électeur</label><input type="number" name="nb_choix_autorises" value="2" min="2" class="form-control"></div>
                    <div><label>Date de début</label><input type="datetime-local" name="starts_at" required class="form-control"></div>
                    <div><label>Date de fin</label><input type="datetime-local" name="ends_at" required class="form-control"></div>
                    <button type="submit" class="btn-primary">Créer le vote</button>
                </div>
            </form>
        @endif

        @foreach($meeting->votes as $vote)
            <div style="margin-top:1rem; padding:1rem; border:1px solid #ddd; border-radius:12px; background:#fff;">
                <h3>{{ $vote->question }}</h3>
                <p><strong>Statut :</strong> {{ $vote->status }} | <strong>Début :</strong> {{ $vote->starts_at }} | <strong>Fin :</strong> {{ $vote->ends_at }}</p>
                @if(auth()->check() && auth()->user()->role === 'syndic' && auth()->user()->syndic?->property_id === $meeting->property_id && $vote->status !== 'closed')
                    <form action="{{ route('votes.close', $vote) }}" method="POST" style="margin-bottom:0.75rem;">
                        @csrf
                        <button type="submit" class="btn-secondary">Clôturer le vote</button>
                    </form>
                @endif
                @if(auth()->check() && auth()->user()->role === 'copropriétaire' && $vote->status === 'open')
                    <form action="{{ route('votes.participate', $vote) }}" method="POST" style="margin-bottom:0.75rem;">
                        @csrf
                        <input type="hidden" name="vote_choice_ids[]" value="">
                        @foreach($vote->voteChoices as $choice)
                            <label style="display:block; margin:0.25rem 0;"><input type="checkbox" name="vote_choice_ids[]" value="{{ $choice->id }}"> {{ $choice->label }}</label>
                        @endforeach
                        <button type="submit" class="btn-primary">Voter</button>
                    </form>
                @endif
                <ul>
                    @foreach($vote->voteChoices as $choice)
                        @php $count = $choice->voteParticipations->count(); @endphp
                        <li>{{ $choice->label }} : {{ $count }} vote{{ $count > 1 ? 's' : '' }}</li>
                    @endforeach
                </ul>
                @if($vote->status === 'closed' && $vote->final_decision)
                    <p><strong>Décision finale :</strong> {{ $vote->final_decision }}</p>
                @endif
                @if(auth()->check() && auth()->user()->role === 'syndic' && auth()->user()->syndic?->property_id === $meeting->property_id)
                    @php $participants = $vote->voteChoices->flatMap(fn($choice) => $choice->voteParticipations->map(fn($p) => $p->owner->office_number ?? 'N/A')); @endphp
                    <p><strong>Participants :</strong> {{ $participants->implode(', ') }}</p>
                @endif
            </div>
        @endforeach
@endsection