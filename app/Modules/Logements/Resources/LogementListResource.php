<?php

namespace App\Modules\Logements\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LogementListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'titre'            => $this->titre,
            'description'      => $this->description,
            'adresse'          => $this->adresse,
            'superficie'       => $this->superficie,
            'nombre_pieces'    => $this->nombre_pieces,
            'loyer'            => $this->loyer,
            'caution'          => $this->caution,
            'statut'           => $this->statut->value,
            'statut_moderation' => $this->statut_moderation->value,
            'created_at'       => $this->created_at,
            'quartier'         => [
                'id'   => $this->quartier->id,
                'nom'  => $this->quartier->nom,
                'ville' => $this->quartier->ville,
            ],
            'type_logement' => [
                'id'      => $this->typeLogement->id,
                'libelle' => $this->typeLogement->libelle,
            ],
            'photo_principale' => $this->photos->where('est_principale', true)->first()
                ? url('storage/' . $this->photos->where('est_principale', true)->first()->chemin)
                : null,
            'nombre_photos'    => $this->photos->count(),
        ];
    }
}
