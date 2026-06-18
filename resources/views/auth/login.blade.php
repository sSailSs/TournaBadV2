@extends('layouts.app')

@section('content')
    <div class="card" style="max-width: 480px; margin: 0 auto;">
        <h1>Connexion</h1>
        <p>Accède à ton espace TournaBad.</p>

        <form method="POST" action="{{ route('login.attempt') }}">
            @csrf

            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>

            <label for="password">Mot de passe</label>
            <input id="password" type="password" name="password" required>

            <label style="display:flex; align-items:center; gap:.5rem; margin-top: 1rem; font-weight: 500;">
                <input style="width:auto; margin-top: 0;" type="checkbox" name="remember" value="1">
                Se souvenir de moi
            </label>

            <button class="btn btn-primary" style="margin-top: 1rem; width: 100%;" type="submit">Se connecter</button>
        </form>

        <a class="btn btn-outline" style="margin-top: .75rem; width: 100%;" href="{{ route('register') }}">
            Pas de compte ? Créer en un
        </a>
    </div>
@endsection
