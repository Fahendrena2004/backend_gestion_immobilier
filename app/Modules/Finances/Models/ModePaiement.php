<?php

namespace App\Modules\Finances\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModePaiement extends Model
{
    use HasFactory;

    protected $table = 'modes_paiement';

    protected $fillable = ['libelle', 'actif'];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }
}
