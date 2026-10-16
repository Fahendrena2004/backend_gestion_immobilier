<?php

namespace App\Modules\Finances\Models;

use App\Modules\Locations\Models\Location;
use App\Shared\Enums\FactureStatus;
use App\Shared\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Facture extends Model
{
    use HasFactory;

    protected $table = 'factures';

    protected $fillable = [
        'location_id',
        'numero_facture',
        'date_emission',
        'date_echeance',
        'montant',
        'periode',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'date_emission' => 'date',
            'date_echeance' => 'date',
            'montant' => 'decimal:2',
            'statut' => FactureStatus::class,
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }

    /**
     * Cumul arithmétique des paiements effectivement validés.
     * Les paiements déclarés ou rejetés n'entrent jamais dans le cumul.
     */
    public function cumulPaiementsValides(): string
    {
        return (string) $this->paiements()
            ->where('statut', PaymentStatus::VALIDE)
            ->sum('montant');
    }

    /**
     * La facture est payée uniquement si le cumul des paiements validés
     * couvre au moins le montant exigible (paiements partiels acceptés).
     */
    public function estEntierementSoldee(): bool
    {
        return bccomp($this->cumulPaiementsValides(), (string) $this->montant, 2) >= 0;
    }
}
