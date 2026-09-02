<?php

namespace App\Modules\Finances\Models;

use App\Models\User;
use App\Shared\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Paiement extends Model
{
    use HasFactory;

    protected $table = 'paiements';

    protected $fillable = [
        'facture_id',
        'mode_paiement_id',
        'admin_validateur_id',
        'date_paiement',
        'montant',
        'reference',
        'preuve',
        'statut',
        'date_validation',
    ];

    protected function casts(): array
    {
        return [
            'date_paiement' => 'datetime',
            'date_validation' => 'datetime',
            'montant' => 'decimal:2',
            'statut' => PaymentStatus::class,
        ];
    }

    public function facture(): BelongsTo
    {
        return $this->belongsTo(Facture::class);
    }

    public function modePaiement(): BelongsTo
    {
        return $this->belongsTo(ModePaiement::class);
    }

    public function adminValidateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_validateur_id');
    }

    public function quittance(): HasOne
    {
        return $this->hasOne(Quittance::class);
    }
}
