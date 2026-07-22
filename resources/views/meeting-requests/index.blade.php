@extends('layouts.app')

@section('title', 'Demandes de réunion')

@section('content')
        <h1>Demandes de réunion</h1>
        @if($requests->isEmpty())
            <div>Aucune demande.</div>
        @else
            <ul>
                @foreach($requests as $r)
                    <li>{{ $r->title }} — Votes: {{ $r->vote_count ?? 0 }} / Seuil: {{ $r->vote_threshold ?? $r->required_threshold }}</li>
                @endforeach
            </ul>
        @endif
@endsection