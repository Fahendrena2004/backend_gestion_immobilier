<?php

namespace App\Modules\Locations\Models;

use App\Modules\Finances\Models\Facture;
use App\Modules\Logements\Models\Logement;
use App\Models\User;
use App\Shared\Enums\LocationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Location extends Model
{
    use HasFactory;

    protected $table = 'locations';

    protected $fillable = [
        'locataire_id',
        'logement_id',
        'date_debut',
        'date_fin',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'statut' => LocationStatus::class,
        ];
    }

    public function locataire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locataire_id');
    }

    public function logement(): BelongsTo
    {
        return $this->belongsTo(Logement::class);
    }

    public function contrat(): HasOne
    {
        return $this->hasOne(Contrat::class);
    }

    public function factures(): HasMany
    {
        return $this->hasMany(Facture::class);
    }
}
