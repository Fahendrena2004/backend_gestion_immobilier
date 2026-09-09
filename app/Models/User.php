<?php

namespace App\Models;

use App\Modules\Demandes\Models\DemandeLocation;
use App\Modules\Finances\Models\Paiement;
use App\Modules\Locations\Models\Location;
use App\Modules\Logements\Models\Logement;
use App\Modules\Notifications\Models\Notification;
use App\Modules\Visites\Models\Visite;
use App\Shared\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'telephone',
        'cin',
        'profession',
        'adresse',
        'niveau_acces',
        'role',
        'is_active',
        'avatar',
    ];

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
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    // --- Relations : Propriétaire ---

    public function logements(): HasMany
    {
        return $this->hasMany(Logement::class, 'proprietaire_id');
    }

    // --- Relations : Locataire ---

    public function demandes(): HasMany
    {
        return $this->hasMany(DemandeLocation::class, 'locataire_id');
    }

    public function visites(): HasMany
    {
        return $this->hasMany(Visite::class, 'locataire_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class, 'locataire_id');
    }

    // --- Relations : Administrateur ---

    public function paiementsValides(): HasMany
    {
        return $this->hasMany(Paiement::class, 'admin_validateur_id');
    }

    // --- Commun à tous les acteurs ---

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function isProprietaire(): bool
    {
        return $this->role === UserRole::PROPRIETAIRE;
    }

    public function isLocataire(): bool
    {
        return $this->role === UserRole::LOCATAIRE;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }
}
