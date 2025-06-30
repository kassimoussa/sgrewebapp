@extends('layouts.auth')

@section('title', 'Mot de passe oublié')

@section('page-title', 'Mot de passe oublié')

@section('description')
    <p class="auth-description">Entrez votre adresse e-mail et nous vous enverrons un lien pour réinitialiser votre mot de passe.</p>
@endsection

@section('content')
    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="form-group">
            <label for="email" class="form-label">Adresse email</label>
            <input id="email" type="email" class="form-input" name="email" value="{{ old('email') }}" required autofocus>
            @error('email')
                <span class="form-error">{{ $message }}</span>
            @enderror
        </div>

        <button type="submit" class="submit-btn">Envoyer le lien de réinitialisation</button>
    </form>
    
    <a href="{{ route('login') }}"  wire:navigate class="back-link">Retour à la connexion</a>
@endsection