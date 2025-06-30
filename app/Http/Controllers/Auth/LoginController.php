<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\User;

class LoginController extends Controller
{
    /**
     * Afficher le formulaire de connexion
     */
    public function show(): View
    {
        return view('auth.login');
    }

    /**
     * Traiter la tentative de connexion
     */
    public function connect(Request $request): RedirectResponse
    {
        // Validation
        $request->validate([
            'identifiant' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'identifiant.required' => 'Le nom d\'utilisateur ou email est requis.',
            'password.required' => 'Le mot de passe est requis.',
        ]);

        $identifiant = $request->input('identifiant');
        $password = $request->input('password');

        // Vérifier si l'utilisateur existe et est actif
        $user = User::where(function($query) use ($identifiant) {
            $query->where('email', $identifiant)
                  ->orWhere('username', $identifiant);
        })->first();

        if (!$user) {
            return back()->withErrors([
                'identifiant' => 'Aucun compte trouvé avec ces identifiants.',
            ])->onlyInput('identifiant');
        }

        if (!$user->is_active) {
            return back()->withErrors([
                'identifiant' => 'Votre compte a été désactivé. Contactez l\'administrateur.',
            ])->onlyInput('identifiant');
        }

        // Vérifier si l'utilisateur a un rôle admin
        if (!$user->isAdmin()) {
            return back()->withErrors([
                'identifiant' => 'Accès non autorisé. Seuls les administrateurs peuvent se connecter.',
            ])->onlyInput('identifiant');
        }

        // Déterminer le champ pour la connexion
        $fieldType = filter_var($identifiant, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        
        $credentials = [
            $fieldType => $identifiant,
            'password' => $password
        ];

        // Tentative de connexion
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // Mettre à jour last_login_at si la colonne existe
            if ($user) {
                $user->update(['last_login_at' => now()]);
            } 

            $userName = $user->prenom ? "{$user->prenom} {$user->nom}" : $user->username;

            return redirect()->intended(route('dashboard'))
                ->with('success', "Bienvenue {$userName} !");
        }

        return back()->withErrors([
            'identifiant' => 'Mot de passe incorrect.',
        ])->onlyInput('identifiant');
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Vous avez été déconnecté.');
    }
}