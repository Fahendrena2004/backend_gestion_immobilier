<?php

namespace App\Modules\Locations\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contrat extends Model
{
    use HasFactory;

    protected $table = 'contrats';

    protected $fillable = [
        'location_id',
        'date_signature',
        'date_debut',
        'date_fin',
        'montant_loyer',
        'montant_caution',
        'conditions',
        'chemin_pdf',
    ];

    protected function casts(): array
    {
        return [
            'date_signature' => 'date',
            'date_debut' => 'date',
            'date_fin' => 'date',
            'montant_loyer' => 'decimal:2',
            'montant_caution' => 'decimal:2',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
