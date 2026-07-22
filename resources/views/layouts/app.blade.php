<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Syndic Professionnel')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="page-fade-in">
    <header class="card-glass" style="position:sticky; top:0; z-index:10; margin:1.2rem auto 0; width:min(var(--container), calc(100vw - 48px)); border-radius:18px; padding:1rem 1.2rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;">
            <a href="{{ route('dashboard') }}" style="font-weight:800; color:var(--color-primary);">Syndic Professionnel</a>
            <div class="header-actions">
                @auth
                    @if(auth()->user()->role === 'copropriétaire' && auth()->user()->owner?->is_council_member)
                        <span class="feature-badge" style="margin-top:0;">Conseil syndical</span>
                    @endif
                @endauth
                @unless(request()->is('/'))<?php echo view('components.notification-dropdown')->render() . view('components.message-dropdown')->render(); ?>@endunless
                @auth
                    <a href="{{ route('logout') }}" class="btn-secondary" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        Déconnexion
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">@csrf</form>
                @endauth
            </div>
        </div>
    </header>

    @auth
        @php
            $ownerHasContribution = auth()->check() && auth()->user()->role === 'copropriétaire' && auth()->user()->owner?->contributions()->exists();
        @endphp
        @unless(request()->is('/'))
            <div class="app-shell">
                <aside class="app-sidebar card-glass" id="app-sidebar">
                    <button type="button" class="sidebar-toggle" id="sidebar-toggle" aria-label="Réduire ou agrandir la barre latérale" aria-expanded="false">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
                    </button>

                    <nav class="sidebar-nav" aria-label="Fonctionnalités de l'espace">
                        @php
                            $currentUser = auth()->user();
                            $syndicProperty = $currentUser->syndic?->property;
                            $hasFirstAssembly = $syndicProperty ? $syndicProperty->hasCompletedFirstAssembly() : false;
                            $ownerReglementProperty = $currentUser->owner?->property;
                        @endphp
                        <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}" data-tooltip="Tableau de bord">
                            <span class="sidebar-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 13h8V3H3z"/><path d="M13 21h8V11h-8z"/><path d="M13 3v6h8V3z"/><path d="M3 21h8v-6H3z"/></svg></span>
                            <span class="sidebar-label">Tableau de bord</span>
                        </a>

                        @if(auth()->user()->role === 'syndic')
                            <a href="{{ route('import.upload') }}" class="sidebar-link {{ request()->routeIs('import.*') ? 'is-active' : '' }}" data-tooltip="Importer le PDF de 1ère assemblée">
                                <span class="sidebar-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 3v12"/><path d="M8 11l4 4 4-4"/><path d="M5 19h14"/></svg></span>
                                <span class="sidebar-label">Importer le PDF de 1ère assemblée</span>
                            </a>

                            <a href="{{ route('owners.index') }}" class="sidebar-link {{ request()->routeIs('owners.*') ? 'is-active' : '' }}" data-tooltip="Gestion des copropriétaires">
                                <span class="sidebar-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
                                <span class="sidebar-label">Gestion des copropriétaires</span>
                            </a>

                            <a href="{{ route('contributions.index') }}" class="sidebar-link {{ request()->routeIs('contributions.*') ? 'is-active' : '' }}" data-tooltip="Cotisations">
                                <span class="sidebar-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 2v20"/><path d="M2 12h20"/><path d="M4 4h16v16H4z" opacity="0.2"/></svg></span>
                                <span class="sidebar-label">Cotisations</span>
                            </a>

                            @if($hasFirstAssembly)
                                <a href="{{ route('expenses.index') }}" class="sidebar-link {{ request()->routeIs('expenses.*') ? 'is-active' : '' }}" data-tooltip="Dépenses">
                                    <span class="sidebar-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 7h16"/><path d="M7 7v10"/><path d="M17 7v10"/><path d="M4 17h16"/><path d="M4 4h16v16H4z"/></svg></span>
                                    <span class="sidebar-label">Dépenses</span>
                                </a>
                            @else
                                <span class="sidebar-link is-disabled" data-tooltip="Dépenses — Bientôt disponible">
                                    <span class="sidebar-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 7h16"/><path d="M7 7v10"/><path d="M17 7v10"/><path d="M4 17h16"/><path d="M4 4h16v16H4z"/></svg></span>
                                    <span class="sidebar-label">Dépenses<small>Bientôt disponible</small></span>
                                </span>
                            @endif

                            @if($hasFirstAssembly)
                                <a href="{{ route('complaints.index') }}" class="sidebar-link {{ request()->routeIs('complaints.*') ? 'is-active' : '' }}" data-tooltip="Réclamations">
                                    <span class="sidebar-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 10a7 7 0 1 1 14 0v8a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/><path d="M10 14h4"/><path d="M10 18h1"/></svg></span>
                                    <span class="sidebar-label">Réclamations</span>
                                </a>
                            @else
                                <span class="sidebar-link is-disabled" data-tooltip="Réclamations — Bientôt disponible">
                                    <span class="sidebar-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 10a7 7 0 1 1 14 0v8a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/><path d="M10 14h4"/><path d="M10 18h1"/></svg></span>
                                    <span class="sidebar-label">Réclamations<small>Bientôt disponible</small></span>
                                </span>
                            @endif

                            @if($hasFirstAssembly)
                                <a href="{{ route('meetings.index') }}" class="sidebar-link {{ request()->routeIs('meetings.*') ? 'is-active' : '' }}" data-tooltip="Réunions &amp; votes">
                                    <span class="sidebar-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M8 7h8"/><path d="M8 12h8"/><path d="M8 17h5"/><path d="M3 5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></span>
                                    <span class="sidebar-label">Réunions &amp; votes</span>
                                </a>
                            @else
                                <span class="sidebar-link is-disabled" data-tooltip="Réunions &amp; votes — Bientôt disponible">
                                    <span class="sidebar-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M8 7h8"/><path d="M8 12h8"/><path d="M8 17h5"/><path d="M3 5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></span>
                                    <span class="sidebar-label">Réunions &amp; votes<small>Bientôt disponible</small></span>
                                </span>
                            @endif

                            @if($hasFirstAssembly)
                                <a href="{{ route('messages.index') }}" class="sidebar-link {{ request()->routeIs('messages.*') ? 'is-active' : '' }}" data-tooltip="Messagerie">
                                    <span class="sidebar-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 4h16v16H4z"/><path d="M8 8h8"/><path d="M8 12h8"/><path d="M8 16h5"/></svg></span>
                                    <span class="sidebar-label">Messagerie</span>
                                </a>
                            @else
                                <span class="sidebar-link is-disabled" data-tooltip="Messagerie — Bientôt disponible">
                                    <span class="sidebar-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 4h16v16H4z"/><path d="M8 8h8"/><path d="M8 12h8"/><path d="M8 16h5"/></svg></span>
                                    <span class="sidebar-label">Messagerie<small>Bientôt disponible</small></span>
                                </span>
                            @endif

                            @if($hasFirstAssembly)
                                <a href="{{ route('tenants.index') }}" class="sidebar-link {{ request()->routeIs('tenants.*') ? 'is-active' : '' }}" data-tooltip="Gestion locataires">
                                    <span class="sidebar-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 12h18"/><path d="M5 12V6a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v6"/><path d="M7 12v8h10v-8"/></svg></span>
                                    <span class="sidebar-label">Gestion locataires</span>
                                </a>
                            @else
                                <span class="sidebar-link is-disabled" data-tooltip="Gestion locataires — Bientôt disponible">
                                    <span class="sidebar-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 12h18"/><path d="M5 12V6a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v6"/><path d="M7 12v8h10v-8"/></svg></span>
                                    <span class="sidebar-label">Gestion locataires<small>Bientôt disponible</small></span>
                                </span>
                            @endif

                            @if($syndicProperty?->reglement_fichier)
                                <a href="{{ route('properties.reglement.download', $syndicProperty) }}" class="sidebar-link {{ request()->routeIs('properties.reglement.*') ? 'is-active' : '' }}" data-tooltip="Règlement de copropriété">
                                    <span class="sidebar-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 12h18"/><path d="M12 3v18"/><path d="M4 4l16 16"/><path d="M20 4L4 20"/></svg></span>
                                    <span class="sidebar-label">Règlement de copropriété</span>
                                </a>
                            @else
                                <a href="{{ route('properties.reglement.form', $syndicProperty) }}" class="sidebar-link {{ request()->routeIs('properties.reglement.*') ? 'is-active' : '' }}" data-tooltip="Téléverser le règlement de copropriété">
                                    <span class="sidebar-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 12h18"/><path d="M12 3v18"/><path d="M4 4l16 16"/><path d="M20 4L4 20"/></svg></span>
                                    <span class="sidebar-label">Règlement de copropriété<small>À téléverser</small></span>
                                </a>
                            @endif
                        @elseif(auth()->user()->role === 'copropriétaire')
                            <a href="{{ route('profile.show') }}" class="sidebar-link {{ request()->routeIs('profile.*') ? 'is-active' : '' }}" data-tooltip="Infos personnelles">
                                <span class="sidebar-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/><path d="M4 20a8 8 0 0 1 16 0"/></svg></span>
                                <span class="sidebar-label">Infos personnelles</span>
                            </a>

                            @if($ownerHasContribution)
                                <a href="{{ route('contributions.owner') }}" class="sidebar-link {{ request()->routeIs('contributions.owner') ? 'is-active' : '' }}" data-tooltip="Ma cotisation">
                                    <span class="sidebar-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 2v20"/><path d="M2 12h20"/><path d="M4 4h16v16H4z" opacity="0.2"/></svg></span>
                                    <span class="sidebar-label">Ma cotisation</span>
                                </a>
                            @else
                                <span class="sidebar-link is-disabled" data-tooltip="Ma cotisation — Bientôt disponible">
                                    <span class="sidebar-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 2v20"/><path d="M2 12h20"/><path d="M4 4h16v16H4z" opacity="0.2"/></svg></span>
                                    <span class="sidebar-label">Ma cotisation<small>Bientôt disponible</small></span>
                                </span>
                            @endif

                            @if($currentUser->owner?->is_council_member)
                                <a href="{{ route('expenses.index') }}" class="sidebar-link {{ request()->routeIs('expenses.*') ? 'is-active' : '' }}" data-tooltip="Dépenses">
                                    <span class="sidebar-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 7h16"/><path d="M7 7v10"/><path d="M17 7v10"/><path d="M4 17h16"/><path d="M4 4h16v16H4z"/></svg></span>
                                    <span class="sidebar-label">Dépenses</span>
                                </a>
                            @endif

                            @if($ownerHasContribution)
                                <a href="{{ route('complaints.index') }}" class="sidebar-link {{ request()->routeIs('complaints.*') ? 'is-active' : '' }}" data-tooltip="Réclamations">
                                    <span class="sidebar-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 10a7 7 0 1 1 14 0v8a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/><path d="M10 14h4"/><path d="M10 18h1"/></svg></span>
                                    <span class="sidebar-label">Réclamations</span>
                                </a>

                                <a href="{{ route('meetings.index') }}" class="sidebar-link {{ request()->routeIs('meetings.*') ? 'is-active' : '' }}" data-tooltip="Réunions &amp; votes">
                                    <span class="sidebar-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M8 7h8"/><path d="M8 12h8"/><path d="M8 17h5"/><path d="M3 5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></span>
                                    <span class="sidebar-label">Réunions &amp; votes</span>
                                </a>

                                <a href="{{ route('messages.index') }}" class="sidebar-link {{ request()->routeIs('messages.*') ? 'is-active' : '' }}" data-tooltip="Messagerie">
                                    <span class="sidebar-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 4h16v16H4z"/><path d="M8 8h8"/><path d="M8 12h8"/><path d="M8 16h5"/></svg></span>
                                    <span class="sidebar-label">Messagerie</span>
                                </a>
                            @else
                                <a href="{{ route('complaints.index') }}" class="sidebar-link {{ request()->routeIs('complaints.*') ? 'is-active' : '' }}" data-tooltip="Réclamations">
                                    <span class="sidebar-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 10a7 7 0 1 1 14 0v8a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/><path d="M10 14h4"/><path d="M10 18h1"/></svg></span>
                                    <span class="sidebar-label">Réclamations</span>
                                </a>

                                <span class="sidebar-link is-disabled" data-tooltip="Réunions &amp; votes — Bientôt disponible">
                                    <span class="sidebar-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M8 7h8"/><path d="M8 12h8"/><path d="M8 17h5"/><path d="M3 5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></span>
                                    <span class="sidebar-label">Réunions &amp; votes<small>Bientôt disponible</small></span>
                                </span>

                                <span class="sidebar-link is-disabled" data-tooltip="Messagerie — Bientôt disponible">
                                    <span class="sidebar-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 4h16v16H4z"/><path d="M8 8h8"/><path d="M8 12h8"/><path d="M8 16h5"/></svg></span>
                                    <span class="sidebar-label">Messagerie<small>Bientôt disponible</small></span>
                                </span>
                            @endif

                            <a href="{{ route('tenants.index') }}" class="sidebar-link {{ request()->routeIs('tenants.*') ? 'is-active' : '' }}" data-tooltip="Locataire de mon bureau">
                                <span class="sidebar-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 12h18"/><path d="M5 12V6a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v6"/><path d="M7 12v8h10v-8"/></svg></span>
                                <span class="sidebar-label">Locataire de mon bureau</span>
                            </a>

                            @if($ownerReglementProperty?->reglement_fichier)
                                <a href="{{ route('properties.reglement.download', $ownerReglementProperty) }}" class="sidebar-link {{ request()->routeIs('properties.reglement.*') ? 'is-active' : '' }}" data-tooltip="Règlement de copropriété">
                                    <span class="sidebar-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 12h18"/><path d="M12 3v18"/><path d="M4 4l16 16"/><path d="M20 4L4 20"/></svg></span>
                                    <span class="sidebar-label">Règlement de copropriété</span>
                                </a>
                            @else
                                <span class="sidebar-link is-disabled" data-tooltip="Règlement de copropriété — Bientôt disponible">
                                    <span class="sidebar-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 12h18"/><path d="M12 3v18"/><path d="M4 4l16 16"/><path d="M20 4L4 20"/></svg></span>
                                    <span class="sidebar-label">Règlement de copropriété<small>Bientôt disponible</small></span>
                                </span>
                            @endif
                        @endif
                    </nav>
                </aside>

                <div class="app-main">
                    <main class="container py-6" style="width:100%; margin:0;">
                        @yield('content')
                    </main>
                </div>
            </div>
        @else
            <main class="container py-6">
                @yield('content')
            </main>
        @endunless
    @else
        <main class="container py-6">
            @yield('content')
        </main>
    @endauth
</body>
</html>