<?php

namespace App\Modules\Logements\Models;

use App\Shared\Services\FileUploadService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Photo extends Model
{
    use HasFactory;

    protected $table = 'photos';

    protected $fillable = ['logement_id', 'chemin', 'est_principale'];

    protected function casts(): array
    {
        return [
            'est_principale' => 'boolean',
        ];
    }

    public function logement(): BelongsTo
    {
        return $this->belongsTo(Logement::class);
    }

    public function cheminMiniature(): string
    {
        $thumb = FileUploadService::thumbnailPath($this->chemin);
        if (Storage::disk('public')->exists($thumb)) {
            return $thumb;
        }

        return $this->chemin;
    }
}
