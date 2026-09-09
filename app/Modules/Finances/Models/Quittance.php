<?php

namespace App\Modules\Finances\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quittance extends Model
{
    use HasFactory;

    protected $table = 'quittances';

    protected $fillable = [
        'paiement_id',
        'numero_quittance',
        'date_emission',
        'chemin_pdf',
    ];

    protected function casts(): array
    {
        return [
            'date_emission' => 'date',
        ];
    }

    public function paiement(): BelongsTo
    {
        return $this->belongsTo(Paiement::class);
    }
}
