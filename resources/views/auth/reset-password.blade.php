@extends('layouts.auth')

@section('title', 'Réinitialisation du mot de passe')

@section('page-title', 'Réinitialisation du mot de passe')

@section('content')
    <form method="POST" action="{{ route('password.update') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <div class="form-group">
            <label for="email" class="form-label">Adresse email</label>
            <input id="email" type="email" class="form-input" name="email" value="{{ $email ?? old('email') }}" required autofocus>
            @error('email')
                <span class="form-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Nouveau mot de passe</label>
            <input id="password" type="password" class="form-input" name="password" required>
            @error('password')
                <span class="form-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="password-confirm" class="form-label">Confirmer le mot de passe</label>
            <input id="password-confirm" type="password" class="form-input" name="password_confirmation" required>
        </div>

        <button type="submit" class="submit-btn">Réinitialiser le mot de passe</button>
    </form>
@endsection