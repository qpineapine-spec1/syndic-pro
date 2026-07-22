@extends('layouts.app')

@section('title', 'Réclamations')

@section('content')
        <style>
            /* === Réclamations : styles scopés à cette page uniquement === */
            .rc-page .page-header { margin-bottom: 1.25rem; }

            .rc-addbar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                padding: 0.9rem 1.25rem;
                margin-bottom: 1rem;
            }
            .rc-addbar__label { font-weight: 700; color: var(--color-primary); font-size: 0.95rem; }
            .rc-addbar__toggle {
                width: 2.35rem;
                height: 2.35rem;
                min-width: 2.35rem;
                padding: 0;
                font-size: 1.2rem;
                line-height: 1;
                border-radius: var(--radius-sm);
            }

            #complaint-form-wrapper { padding: 0 1.25rem 1.25rem; }

            .rc-table-card { padding: 0; overflow: hidden; }
            .rc-table-scroll { overflow-x: auto; }

            .rc-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
                min-width: 780px;
            }
            .rc-table thead th {
                text-align: left;
                padding: 0.85rem 1rem;
                font-size: 0.78rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: var(--color-text-muted);
                border-bottom: 1px solid var(--color-border);
                white-space: nowrap;
            }
            .rc-table tbody td {
                padding: 0.85rem 1rem;
                border-bottom: 1px solid rgba(27, 58, 92, 0.06);
                vertical-align: middle;
            }
            .rc-table tbody tr:last-child td { border-bottom: none; }
            .rc-table tbody tr:hover { background: rgba(217, 164, 65, 0.05); }

            .rc-subject { font-weight: 600; color: var(--color-text); }
            .rc-desc { color: var(--color-text-muted); font-size: 0.85rem; margin-top: 0.2rem; }
            .rc-muted { color: var(--color-text-muted); font-size: 0.85rem; }

            /* Ligne d'actions générique : aligne tout sur un même axe horizontal, centré verticalement */
            .rc-row {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                flex-wrap: wrap;
            }
            .rc-col {
                display: flex;
                flex-direction: column;
                gap: 0.4rem;
            }

            /* Uniformisation stricte : boutons + select partagent la même hauteur, le même rayon et le même padding */
            .rc-control {
                height: 2.35rem;
                border-radius: var(--radius-sm);
                padding: 0 0.9rem;
                font-size: 0.88rem;
                font-weight: 600;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                line-height: 1;
                box-sizing: border-box;
                white-space: nowrap;
            }
            a.rc-control, button.rc-control { cursor: pointer; }

            .rc-btn-primary {
                background: linear-gradient(135deg, var(--color-primary), #244b6e);
                color: #fff;
                border: 1px solid transparent;
                box-shadow: 0 6px 14px rgba(27, 58, 92, 0.18);
                transition: all 0.2s ease;
            }
            .rc-btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 10px 18px rgba(27, 58, 92, 0.24); }

            .rc-btn-secondary {
                background: transparent;
                border: 1px solid var(--color-accent);
                color: var(--color-primary);
                text-decoration: none;
                transition: all 0.2s ease;
            }
            .rc-btn-secondary:hover:not(:disabled) { transform: translateY(-1px); background: rgba(217, 164, 65, 0.08); }

            .rc-select {
                border: 1px solid var(--color-border);
                background: #fff;
                color: var(--color-text);
                min-width: 128px;
            }

            .rc-file {
                height: 2.35rem;
                display: inline-flex;
                align-items: center;
                font-size: 0.82rem;
                max-width: 170px;
            }

            .rc-badge { margin-top: 0; }

            @media (max-width: 720px) {
                .rc-addbar { flex-wrap: wrap; }
                .rc-row { gap: 0.4rem; }
                .rc-file { max-width: 140px; }
                .rc-table { min-width: 680px; }
            }
        </style>

        <section class="page-header rc-page">
            <div>
                <div class="feature-badge">Réclamations</div>
                <h1 class="page-title">Suivi des réclamations</h1>
            </div>
            <a href="{{ route('dashboard') }}" class="btn-secondary">Retour</a>
        </section>

        @include('partials.flash')

        @if(auth()->user()->role === 'copropriétaire' && auth()->user()->owner)
            <section class="card-glass rc-addbar">
                <span class="rc-addbar__label">Nouvelle réclamation</span>
                <button type="button" id="toggle-complaint-form" class="btn-secondary rc-addbar__toggle">+</button>
            </section>

            <section class="card-glass" id="complaint-form-wrapper" style="display:none; margin-bottom:1.25rem;">
                    <form action="{{ route('complaints.store') }}" method="POST" enctype="multipart/form-data" style="display:grid; gap:0.75rem; max-width:640px;">
                        @csrf
                        <input type="hidden" name="owner_id" value="{{ auth()->user()->owner->id }}">
                        <input type="hidden" name="property_id" value="{{ auth()->user()->owner->property_id }}">
                        <div style="display:grid; gap:0.4rem;">
                            <label for="subject">Motif</label>
                            <input id="subject" name="subject" type="text" required maxlength="255" value="{{ old('subject') }}" placeholder="Ex. Fuite d’eau">
                            @error('subject') <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                        <div style="display:grid; gap:0.4rem;">
                            <label for="date">Date</label>
                            <input id="date" name="date" type="date" value="{{ old('date', now()->toDateString()) }}">
                            @error('date') <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                        <div style="display:grid; gap:0.4rem;">
                            <label for="category">Catégorie</label>
                            <select id="category" name="category">
                                <option value="eau" {{ old('category') === 'eau' ? 'selected' : '' }}>Eau</option>
                                <option value="electricite" {{ old('category') === 'electricite' ? 'selected' : '' }}>Électricité</option>
                                <option value="ascenseur" {{ old('category') === 'ascenseur' ? 'selected' : '' }}>Ascenseur</option>
                                <option value="autre" {{ old('category', 'autre') === 'autre' ? 'selected' : '' }}>Autre</option>
                            </select>
                            @error('category') <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                        <div style="display:grid; gap:0.4rem;">
                            <label for="priority">Priorité</label>
                            <select id="priority" name="priority">
                                <option value="faible" {{ old('priority') === 'faible' ? 'selected' : '' }}>Faible</option>
                                <option value="normale" {{ old('priority', 'normale') === 'normale' ? 'selected' : '' }}>Normale</option>
                                <option value="elevee" {{ old('priority') === 'elevee' ? 'selected' : '' }}>Élevée</option>
                            </select>
                            @error('priority') <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                        <div style="display:grid; gap:0.4rem;">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description') <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                        <div style="display:grid; gap:0.4rem;">
                            <label for="fichier_joint">Pièce jointe (jpg, png, pdf, doc, docx — 5 Mo max)</label>
                            <input id="fichier_joint" name="fichier_joint" type="file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                            @error('fichier_joint') <div class="form-error">{{ $message }}</div> @enderror
                        </div>
                        <button type="submit" class="btn-primary" style="justify-self:start;">Valider</button>
                    </form>
            </section>
        @endif

        <section class="card-glass rc-table-card">
            @if($complaints->isEmpty())
                <div class="dropdown-empty">Aucune réclamation pour cette propriété.</div>
            @else
                <div class="rc-table-scroll">
                    <table class="rc-table">
                        <thead>
                            <tr>
                                <th>Motif</th>
                                <th>Catégorie</th>
                                <th>Date</th>
                                @if(auth()->user()->role === 'syndic')
                                    <th>Copropriétaire</th>
                                @endif
                                <th>Statut</th>
                                <th>Priorité</th>
                                <th>Pièce jointe</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($complaints as $c)
                                <tr>
                                    <td>
                                        <div class="rc-subject">{{ $c->subject }}</div>
                                        @if($c->description)
                                            <div class="rc-desc">{{ $c->description }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $c->categoryLabel() }}</td>
                                    <td>{{ $c->date?->format('d/m/Y') ?? '—' }}</td>
                                    @if(auth()->user()->role === 'syndic')
                                        <td>{{ $c->owner?->user?->name ?? $c->owner?->user?->email ?? 'Copropriétaire' }}</td>
                                    @endif
                                    <td>
                                        <div class="rc-col">
                                            <span class="feature-badge rc-badge">{{ $c->statusLabel() }}</span>

                                            @if(auth()->user()->role === 'syndic')
                                                <form action="{{ route('complaints.status', $c) }}" method="POST" class="rc-row">
                                                    @csrf
                                                    <select name="status" class="rc-control rc-select">
                                                        <option value="nouvelle" {{ $c->status === 'nouvelle' ? 'selected' : '' }}>Nouvelle</option>
                                                        <option value="en_cours" {{ $c->status === 'en_cours' ? 'selected' : '' }}>En cours</option>
                                                        <option value="finie" {{ $c->status === 'finie' ? 'selected' : '' }}>Terminée</option>
                                                        <option value="annulee" {{ $c->status === 'annulee' ? 'selected' : '' }}>Annulée</option>
                                                    </select>
                                                    <button type="submit" class="rc-control rc-btn-primary">Mettre à jour</button>
                                                </form>
                                            @endif

                                            @if(auth()->user()->role === 'copropriétaire')
                                                @if($c->canBeValidatedByOwner())
                                                    <form action="{{ route('complaints.validate', $c) }}" method="POST" class="rc-row">
                                                        @csrf
                                                        <button type="submit" class="rc-control rc-btn-primary">✔ Valider</button>
                                                    </form>
                                                @elseif($c->isValidatedByOwner())
                                                    <span class="rc-muted">Validée le {{ $c->validated_at->format('d/m/Y') }}</span>
                                                @else
                                                    <span class="rc-muted">—</span>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $c->priorityLabel() }}</td>
                                    <td>
                                        <div class="rc-row">
                                            @if($c->fichier_joint)
                                                <a href="{{ route('complaints.attachment', $c) }}" download class="rc-control rc-btn-secondary">Voir</a>
                                            @else
                                                <span class="rc-muted">Aucune</span>
                                            @endif

                                            @if(auth()->user()->role === 'copropriétaire')
                                                <form action="{{ route('complaints.upload', $c) }}" method="POST" enctype="multipart/form-data" class="rc-row">
                                                    @csrf
                                                    <input type="file" name="fichier_joint" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" class="rc-file">
                                                    <button type="submit" class="rc-control rc-btn-secondary">{{ $c->fichier_joint ? 'Remplacer' : 'Joindre' }}</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <script>
            const toggleBtn = document.getElementById('toggle-complaint-form');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function () {
                    const wrapper = document.getElementById('complaint-form-wrapper');
                    const isHidden = wrapper.style.display === 'none' || wrapper.style.display === '';
                    wrapper.style.display = isHidden ? 'block' : 'none';
                    toggleBtn.textContent = isHidden ? '−' : '+';
                });
            }
        </script>
@endsection