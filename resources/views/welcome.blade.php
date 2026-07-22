@extends('layouts.app')

@section('title', 'Syndic Professionnel')

@section('content')
            <section class="card-glass hero-card">
                <div class="hero-grid">
                    <div class="hero-copy">
                        <div class="feature-badge">Syndic Professionnel</div>
                        <h1 class="page-title" style="margin-top: 1rem;">Gérez votre copropriété avec clarté.</h1>
                        <p class="page-subtitle" style="margin-top: 1rem;">
                            Centralisez les assemblées, les estimations, les cotisations et le suivi des copropriétaires dans un espace dédié et sécurisé.
                        </p>
                        <div class="welcome-cta">
                            <a href="{{ route('register') }}" class="btn-primary">S'inscrire</a>
                            <a href="{{ route('login') }}" class="btn-secondary">Se connecter</a>
                            <a href="{{ route('templates.modele-premiere-assemblee') }}" class="btn-secondary">Télécharger le modèle de 1ère assemblée</a>
                        </div>
                    </div>
                    <div class="hero-visual">
                        <img src="{{ asset('images/image1.jpeg') }}" alt="Logo" />
                    </div>
                </div>
            </section>

            <section class="card-glass" style="padding: 2rem; margin-top: 2rem;">
                <div class="bubbles-grid">
                    <article class="bubble">
                        <span class="bubble-icon"><i class="ti ti-credit-card" aria-hidden="true"></i></span>
                        <h3>Cotisations</h3>
                        <p>Suivez la répartition et le paiement des charges de chaque copropriétaire.</p>
                    </article>
                    <article class="bubble">
                        <span class="bubble-icon"><i class="ti ti-receipt" aria-hidden="true"></i></span>
                        <h3>Dépenses</h3>
                        <p>Enregistrez et justifiez les dépenses de l'immeuble en toute transparence.</p>
                    </article>
                    <article class="bubble">
                        <span class="bubble-icon"><i class="ti ti-message-report" aria-hidden="true"></i></span>
                        <h3>Réclamations</h3>
                        <p>Signalez et suivez le traitement des réclamations des copropriétaires.</p>
                    </article>
                    <article class="bubble">
                        <span class="bubble-icon"><i class="ti ti-calendar-event" aria-hidden="true"></i></span>
                        <h3>Réunions &amp; votes</h3>
                        <p>Organisez les assemblées et validez les décisions par vote.</p>
                    </article>
                    <article class="bubble">
                        <span class="bubble-icon"><i class="ti ti-chart-line" aria-hidden="true"></i></span>
                        <h3>Prédiction Machine Learning</h3>
                        <p>Anticipez les besoins financiers grâce à des prévisions intelligentes.</p>
                    </article>
                    <article class="bubble">
                        <span class="bubble-icon"><i class="ti ti-messages" aria-hidden="true"></i></span>
                        <h3>Messagerie</h3>
                        <p>Échangez directement avec le syndic ou les copropriétaires.</p>
                    </article>
                </div>
            </section>

            <section class="reglement-section">
                <div class="card-glass reglement-card">
                    <div>
                        <div class="feature-badge">Règlement</div>
                        <h2 style="margin-top: 0.8rem;">Consultez le règlement intérieur</h2>
                    </div>
                    @if(!empty($reglement) && $reglement->reglement_fichier)
                        <a href="{{ route('properties.reglement.download', $reglement) }}" class="btn-secondary">Télécharger le règlement</a>
                    @else
                        <button class="btn-secondary" disabled>Règlement pas encore disponible</button>
                    @endif
                </div>
            </section>

            <footer class="app-footer">
                <div class="app-footer-inner">
                    <div>
                        <span class="app-footer-brand">Syndic Professionnel</span>
                        <span> — v1.0.0 · © {{ date('Y') }}. Tous droits réservés.</span>
                    </div>
                    <div class="app-footer-links">
                        <a href="{{ route('login') }}">Connexion</a>
                        <a href="{{ route('register') }}">Inscription</a>
                        <a href="{{ route('templates.modele-premiere-assemblee') }}">Politique de travail</a>
                    </div>
                </div>
            </footer>
@endsection