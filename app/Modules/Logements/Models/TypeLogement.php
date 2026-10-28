<?php

namespace App\Modules\Logements\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeLogement extends Model
{
    use HasFactory;

    protected $table = 'types_logement';

    protected $fillable = ['libelle', 'est_valide'];

    protected function casts(): array
    {
        return [
            'est_valide' => 'boolean',
        ];
    }

    public function logements(): HasMany
    {
        return $this->hasMany(Logement::class);
    }
}
