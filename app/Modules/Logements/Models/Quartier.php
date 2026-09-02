<?php

namespace App\Modules\Logements\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quartier extends Model
{
    use HasFactory;

    protected $table = 'quartiers';

    protected $fillable = ['nom', 'ville'];

    public function logements(): HasMany
    {
        return $this->hasMany(Logement::class);
    }
}
