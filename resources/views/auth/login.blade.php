@extends('layouts.auth')

@section('title', 'Connexion Admin')

@section('page-title', 'Connexion')

@section('content')
    <form method="POST" action="{{ route('connect') }}">
        @csrf

        <div class="form-group">
            <label for="identifiant" class="form-label">Nom d'utilisateur ou Email</label>
            <input id="identifiant" type="text" class="form-input" name="identifiant" value="{{ old('identifiant') }}" required autofocus>
            @error('identifiant')
                <span class="form-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Mot de passe</label>
            <input id="password" type="password" class="form-input" name="password" required>
            @error('password')
                <span class="form-error">{{ $message }}</span>
            @enderror
            <a href="{{ route('password.request') }}"  wire:navigate class="form-forgot">Mot de passe oublié?</a>
        </div>

        {{-- <div class="remember-me">
            <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
            <label for="remember">Se souvenir de moi</label>
        </div> --}}

        <button type="submit" class="submit-btn">Se connecter</button>
    </form>
@endsection