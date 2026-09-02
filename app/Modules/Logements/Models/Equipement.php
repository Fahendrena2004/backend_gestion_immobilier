<?php

namespace App\Modules\Logements\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Equipement extends Model
{
    use HasFactory;

    protected $table = 'equipements';

    protected $fillable = ['libelle'];

    public function logements(): BelongsToMany
    {
        return $this->belongsToMany(Logement::class, 'logement_equipement');
    }
}
