@extends('layouts.app')

@section('content')
    <section class="hero card" style="text-align:center;">
        <p class="badge" style="margin:0 auto 0.85rem; width:fit-content;">Fin du tournoi</p>
        <h1 style="margin-bottom:.35rem;">{{ $tournament->name }}</h1>
        <p style="margin:0; color: rgba(255,255,255,0.82);">Classement final du tournoi</p>
    </section>

    @php
        $topPlayers = $players->take(3)->values();
        $restPlayers = $players->slice(3)->values();
        $podiumOrder = [1, 0, 2];
    @endphp

    <section class="card" style="margin-top:1rem;">
        <div class="final-podium">
            @foreach ($podiumOrder as $index)
                @php $player = $topPlayers[$index] ?? null; @endphp
                <article class="final-podium-card final-podium-place-{{ $index + 1 }} {{ $index === 0 ? 'final-podium-first' : '' }}">
                    <div class="final-rank">{{ $index + 1 }}</div>
                    @if ($player)
                        <h2>{{ $player->full_name ?: $player->first_name }}</h2>
                        <p class="muted" style="margin:.25rem 0 0;">{{ $player->points }} points</p>
                        <p style="margin:.35rem 0 0; font-weight:700;">{{ $player->wins ?? 0 }} victoire(s)</p>
                    @else
                        <h2 style="opacity:.35;">-</h2>
                        <p class="muted" style="margin:.25rem 0 0;">{{ ($isTeamPoints ?? false) ? 'Aucune equipe' : 'Aucun joueur' }}</p>
                    @endif
                </article>
            @endforeach
        </div>
    </section>

    <section class="grid grid-2" style="margin-top:1rem; align-items:start;">
        <article class="card">
            <h3 style="margin-bottom:.35rem;">Le reste du classement</h3>
            <p class="muted" style="margin-top:0;">À partir de la 4e place</p>

            <ol class="final-list">
                @forelse ($restPlayers as $player)
                    <li class="final-list-item">
                        <span class="final-list-rank">{{ $loop->iteration + 3 }}</span>
                        <div class="final-list-main">
                            <strong>{{ $player->full_name ?: $player->first_name }}</strong>
                            <span class="muted">{{ $player->wins ?? 0 }} victoire(s) • {{ $player->losses ?? 0 }} défaite(s)</span>
                        </div>
                        <span class="final-list-points">{{ $player->points }} pts</span>
                    </li>
                @empty
                    <li class="final-list-item">
                        <span class="muted">{{ ($isTeamPoints ?? false) ? 'Aucune autre equipe a classer.' : 'Aucun autre joueur a classer.' }}</span>
                    </li>
                @endforelse
            </ol>
        </article>

        <article class="card">
            <h3 style="margin-bottom:.35rem;">Résumé</h3>
            <p><strong>{{ $players->count() }}</strong> {{ ($isTeamPoints ?? false) ? 'equipe(s) classee(s)' : 'joueur(s) classe(s)' }}</p>
            <p><strong>{{ $players->sum('points') }}</strong> points cumulés</p>
            <p><strong>{{ $players->first()->points ?? 0 }}</strong> points pour le leader</p>
            <div style="margin-top:1rem; display:flex; gap:.7rem; flex-wrap:wrap;">
                <a class="btn btn-outline" href="{{ route('tournaments.points', $tournament) }}">Voir les points</a>
                <a class="btn btn-outline" href="{{ route('tournaments.show', $tournament) }}">Retour au tournoi</a>
            </div>
        </article>
    </section>
@endsection
