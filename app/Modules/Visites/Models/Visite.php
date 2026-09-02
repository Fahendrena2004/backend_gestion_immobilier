<?php

namespace App\Modules\Visites\Models;

use App\Modules\Logements\Models\Logement;
use App\Models\User;
use App\Shared\Enums\VisiteStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visite extends Model
{
    use HasFactory;

    protected $table = 'visites';

    protected $fillable = [
        'locataire_id',
        'logement_id',
        'date_proposee',
        'statut',
        'resultat',
    ];

    protected function casts(): array
    {
        return [
            'date_proposee' => 'datetime',
            'statut' => VisiteStatus::class,
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
}
