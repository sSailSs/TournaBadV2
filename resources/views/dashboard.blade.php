@extends('layouts.app')

@section('content')
    <section class="card">
        <div style="display:flex; align-items:start; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
            <div>
                <h1 style="margin-bottom:.35rem;">Compte</h1>
                <p style="margin:0;">Infos du compte, mot de passe, et gestion de tes tournois.</p>
            </div>
            <a class="btn btn-primary" href="{{ route('tournaments.create') }}">Creer un tournoi</a>
        </div>
    </section>

    <section class="grid grid-2" style="margin-top: 1rem; align-items:start;">
        <article class="card">
            <h2 style="margin-bottom:.35rem;">Profil</h2>
            <p style="margin-top:0;">Nom d'utilisateur et email.</p>

            <form method="POST" action="{{ route('dashboard.profile.update') }}" style="display:grid; gap:.85rem; max-width: 520px;">
                @csrf
                @method('PATCH')

                <label>
                    Nom
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
                </label>

                <label>
                    Email
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                </label>

                <button class="btn btn-primary" type="submit" style="width:fit-content;">Enregistrer</button>
            </form>
        </article>

        <article class="card">
            <h2 style="margin-bottom:.35rem;">Mot de passe</h2>
            <p style="margin-top:0;">Change ton mot de passe (minimum 8 caracteres).</p>

            <form method="POST" action="{{ route('dashboard.password.update') }}" style="display:grid; gap:.85rem; max-width: 520px;">
                @csrf
                @method('PATCH')

                <label>
                    Mot de passe actuel
                    <input type="password" name="current_password" required autocomplete="current-password">
                </label>

                <label>
                    Nouveau mot de passe
                    <input type="password" name="password" required autocomplete="new-password">
                </label>

                <label>
                    Confirmation
                    <input type="password" name="password_confirmation" required autocomplete="new-password">
                </label>

                <button class="btn btn-primary" type="submit" style="width:fit-content;">Mettre a jour</button>
            </form>
        </article>
    </section>

    <section class="grid grid-3" style="margin-top: 1rem;">
        <article class="card">
            <h3>Mes tournois</h3>
            <p><strong>{{ $stats['my_tournaments'] }}</strong> tournoi(x)</p>
        </article>

        <article class="card">
            <h3>Email</h3>
            <p><strong>{{ $user->email }}</strong></p>
        </article>

        <article class="card">
            <h3>Inscription</h3>
            <p><strong>{{ $user->created_at?->format('d/m/Y') }}</strong></p>
        </article>
    </section>

    <section class="card" style="margin-top: 1rem;">
        <div style="display:flex; align-items:start; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
            <div>
                <h2 style="margin-bottom:.35rem;">Mes tournois</h2>
                <p style="margin-top:0;">Ouvre, modifie, ou supprime un tournoi.</p>
            </div>
            <a class="btn btn-outline" href="{{ route('tournaments.index') }}">Voir la page Tournois</a>
        </div>

        @if ($tournaments->isEmpty())
            <p style="margin-top:1rem;">Aucun tournoi pour le moment.</p>
        @else
            <table class="table" style="margin-top:1rem;">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Date</th>
                        <th>Terrains</th>
                        <th>Joueurs</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tournaments as $tournament)
                        <tr>
                            <td>
                                <a href="{{ route('tournaments.show', $tournament) }}">{{ $tournament->name }}</a>
                            </td>
                            <td>{{ $tournament->starts_on?->format('d/m/Y') }}</td>
                            <td>{{ $tournament->courts_count }}</td>
                            <td>{{ $tournament->players_count }}</td>
                            <td>{{ ucfirst($tournament->status) }}</td>
                            <td style="display:flex; gap:.5rem; flex-wrap:wrap;">
                                <a class="btn btn-outline" href="{{ route('tournaments.show', $tournament) }}">Ouvrir</a>
                                <a class="btn btn-outline" href="{{ route('tournaments.settings', $tournament) }}">Modifier</a>
                                <form method="POST" action="{{ route('tournaments.destroy', $tournament) }}" onsubmit="return confirm('Supprimer ce tournoi ? Cela supprimera aussi les tours, matchs et joueurs.');" style="margin:0;">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline" type="submit" style="border-color: color-mix(in srgb, var(--danger) 35%, var(--line) 65%); color: var(--danger);">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="margin-top:1rem;">
                {{ $tournaments->links() }}
            </div>
        @endif
    </section>
@endsection
