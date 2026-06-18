@extends('layouts.app')

@section('content')
    @php
        $activeAccountMenu = 'account';
        $profilePhotoUrl = $user->profile_photo_path ? asset('storage/'.$user->profile_photo_path) : null;
        $initials = collect(explode(' ', $user->name))
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    @endphp

    @include('dashboard._account_nav', ['activeAccountMenu' => $activeAccountMenu])

    <div class="account-page-wrap">
        <section class="card">
            <div style="display:flex; align-items:start; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
                <div>
                    <h1 style="margin-bottom:.35rem;">Compte</h1>
                    <p style="margin:0;">Infos du compte, photo de profil et mot de passe.</p>
                </div>
                <a class="btn btn-primary" href="{{ route('tournaments.create') }}">Creer un tournoi</a>
            </div>
        </section>

        <section class="grid grid-2" style="margin-top:1rem; align-items:stretch;">
            <article class="card" style="height:100%;">
                <form method="POST" action="{{ route('dashboard.profile.update') }}" enctype="multipart/form-data" style="display:grid; gap:.85rem; height:100%;">
                    @csrf
                    @method('PATCH')

                    <div style="display:flex; align-items:center; gap:1rem; flex-wrap:wrap; margin-bottom:.2rem;">
                        <label class="account-avatar" aria-label="Modifier la photo de profil">
                            @if ($profilePhotoUrl)
                                <img src="{{ $profilePhotoUrl }}" alt="Photo de profil de {{ $user->name }}">
                            @else
                                <span>{{ $initials ?: 'TB' }}</span>
                            @endif
                            <input class="account-file-input" type="file" name="profile_photo" accept="image/*">
                        </label>

                        <div>
                            <h2 style="margin-bottom:.35rem;">Profil</h2>
                            <p style="margin:0;">Clique sur le rond pour modifier la photo.</p>
                        </div>
                    </div>

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

            <article class="card" style="height:100%;">
                <h2 style="margin-bottom:.35rem;">Mot de passe</h2>
                <p style="margin-top:0;">Change ton mot de passe (minimum 8 caracteres).</p>

                <form method="POST" action="{{ route('dashboard.password.update') }}" style="display:grid; gap:.85rem;">
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

        @if ($user->is_admin)
            <section class="card" style="margin-top:1rem;">
                <div style="display:flex; align-items:start; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
                    <div>
                        <h2 style="margin-bottom:.35rem;">Administration</h2>
                        <p style="margin:0;">Vue minimale des comptes inscrits.</p>
                    </div>
                    <div class="list">
                        <span class="tag">{{ $adminStats['users_count'] ?? 0 }} utilisateur(s)</span>
                        <span class="tag">{{ $adminStats['tournaments_count'] ?? 0 }} tournoi(s)</span>
                    </div>
                </div>

                <div style="margin-top:1rem;">
                    <h3 style="margin-bottom:.6rem;">Utilisateurs</h3>
                    <ul class="mini-ranking-list">
                        @forelse ($adminUsers as $adminUser)
                            <li class="mini-ranking-item" style="grid-template-columns: minmax(0, 1fr);">
                                <span class="mini-ranking-name">{{ $adminUser->name }}</span>
                            </li>
                        @empty
                            <li class="mini-ranking-item" style="grid-template-columns: minmax(0, 1fr);">
                                <span class="mini-ranking-name">Aucun utilisateur</span>
                            </li>
                        @endforelse
                    </ul>
                </div>
            </section>
        @endif
    </div>
@endsection
