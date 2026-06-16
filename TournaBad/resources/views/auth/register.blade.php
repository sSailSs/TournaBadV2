@extends('layouts.app')

@section('content')
    <div class="card" style="max-width: 540px; margin: 0 auto;">
        <h1>Creer un compte</h1>
        <p>Commence la gestion de ton tournoi TournaBad.</p>

        <form method="POST" action="{{ route('register.store') }}">
            @csrf

            <label for="name">Nom</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required>

            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required>

            <label for="password">Mot de passe</label>
            <input id="password" type="password" name="password" required>

            <label for="password_confirmation">Confirmation</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required>

            <button class="btn btn-primary" style="margin-top: 1rem; width: 100%;" type="submit">Creer mon compte</button>
        </form>
    </div>
@endsection
