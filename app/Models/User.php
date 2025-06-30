<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable; 
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use  HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'username',
        'email',
        'password',
        'nom',
        'prenom',
        'role',
        'is_active'
    ]; 
    
    // Vérifier si l'utilisateur a un rôle spécifique
    public function hasRole($role)
    {
        return $this->role === $role;
    }
    
    // Vérifier si l'utilisateur a l'un des rôles fournis
    public function hasAnyRole($roles)
    {
        return in_array($this->role, (array) $roles);
    }
    
    // Vérifier si l'utilisateur est un super administrateur
    public function isSuperAdmin()
    {
        return $this->role === 'super_admin';
    }
    
    // Vérifier si l'utilisateur est un administrateur
    public function isAdmin()
    {
        return $this->role === 'admin' || $this->role === 'super_admin';
    }
    
    // Vérifier si l'utilisateur est un agent
    public function isAgent()
    {
        return $this->role === 'agent';
    }
    
    // Vérifier si l'utilisateur est un superviseur
    public function isSuperviseur()
    {
        return $this->role === 'superviseur';
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];
}
