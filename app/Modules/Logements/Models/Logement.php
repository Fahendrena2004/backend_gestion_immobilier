<?php

namespace App\Modules\Logements\Models;

use App\Modules\Demandes\Models\DemandeLocation;
use App\Modules\Locations\Models\Location;
use App\Modules\Visites\Models\Visite;
use App\Shared\Enums\LogementStatus;
use App\Shared\Enums\ModerationStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Logement extends Model
{
    use HasFactory;

    protected $table = 'logements';

    protected $fillable = [
        'proprietaire_id',
        'quartier_id',
        'type_logement_id',
        'titre',
        'description',
        'adresse',
        'superficie',
        'nombre_pieces',
        'loyer',
        'caution',
        'statut',
        'statut_moderation',
    ];

    protected function casts(): array
    {
        return [
            'superficie' => 'decimal:2',
            'loyer' => 'decimal:2',
            'caution' => 'decimal:2',
            'statut' => LogementStatus::class,
            'statut_moderation' => ModerationStatus::class,
        ];
    }

    public function proprietaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proprietaire_id');
    }

    public function quartier(): BelongsTo
    {
        return $this->belongsTo(Quartier::class);
    }

    public function typeLogement(): BelongsTo
    {
        return $this->belongsTo(TypeLogement::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    public function photoPrincipale(): HasMany
    {
        return $this->photos()->where('est_principale', true);
    }

    public function equipements(): BelongsToMany
    {
        return $this->belongsToMany(Equipement::class, 'logement_equipement');
    }

    public function demandes(): HasMany
    {
        return $this->hasMany(DemandeLocation::class);
    }

    public function visites(): HasMany
    {
        return $this->hasMany(Visite::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }
}
