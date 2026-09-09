<?php

namespace App\Modules\Demandes\Models;

use App\Modules\Logements\Models\Logement;
use App\Models\User;
use App\Shared\Enums\DemandeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemandeLocation extends Model
{
    use HasFactory;

    protected $table = 'demandes_location';

    protected $fillable = [
        'locataire_id',
        'logement_id',
        'message',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'statut' => DemandeStatus::class,
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
