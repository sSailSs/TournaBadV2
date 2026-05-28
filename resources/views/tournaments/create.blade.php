@extends('layouts.app')

@section('content')
    <section class="card" style="max-width: 760px; margin: 0 auto;">
        <h1>Creer un tournoi</h1>
        <p>Renseigne les informations de base du tournoi. Le tournoi sera cree en 2v2 par defaut.</p>

        <form method="POST" action="{{ route('tournaments.store') }}">
            @csrf

            <label for="name">Nom du tournoi</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required>

            <div class="grid grid-2">
                <div>
                    <label for="starts_on">Date</label>
                    <input id="starts_on" type="date" name="starts_on" value="{{ old('starts_on') }}" required>
                </div>

                <div>
                    <label for="courts_count">Nombre de terrains</label>
                    <input id="courts_count" type="number" min="1" max="20" name="courts_count" value="{{ old('courts_count', 2) }}" required>
                </div>
            </div>

            <div class="grid grid-2">
                <div>
                    <label for="round_duration_minutes">Duree d'un tour - minutes</label>
                    <input id="round_duration_minutes" type="number" min="0" max="120" name="round_duration_minutes" value="{{ old('round_duration_minutes', 12) }}" required>
                </div>

                <div>
                    <label for="round_duration_seconds">Duree d'un tour - secondes</label>
                    <input id="round_duration_seconds" type="number" min="0" max="59" name="round_duration_seconds" value="{{ old('round_duration_seconds', 0) }}" required>
                </div>
            </div>

            <label for="description">Description (optionnelle)</label>
            <input id="description" type="text" name="description" value="{{ old('description') }}" placeholder="Ex: tournoi interne du vendredi soir">

            <div style="display:flex; gap:.7rem; flex-wrap:wrap; margin-top:1rem;">
                <button class="btn btn-primary" type="submit">Creer le tournoi</button>
                <a class="btn btn-outline" href="{{ route('tournaments.index') }}">Annuler</a>
            </div>
        </form>
    </section>
@endsection
