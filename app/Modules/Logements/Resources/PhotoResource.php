<?php

namespace App\Modules\Logements\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhotoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'chemin'        => url('storage/' . $this->chemin),
            'est_principale' => $this->est_principale,
        ];
    }
}
