<?php

namespace App\Modules\Logements\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LogementResource extends JsonResource
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
            'updated_at'       => $this->updated_at,
            'quartier' => [
                'id'    => $this->quartier->id,
                'nom'   => $this->quartier->nom,
                'ville' => $this->quartier->ville,
            ],
            'type_logement' => [
                'id'      => $this->typeLogement->id,
                'libelle' => $this->typeLogement->libelle,
            ],
            'proprietaire' => [
                'id'     => $this->proprietaire->id,
                'name'   => $this->proprietaire->name,
                'email'  => $this->proprietaire->email,
                'avatar' => $this->proprietaire->avatar,
            ],
            'photos' => PhotoResource::collection($this->whenLoaded('photos')),
            'photo_principale' => $this->photos->where('est_principale', true)->first()
                ? url('storage/' . $this->photos->where('est_principale', true)->first()->chemin)
                : null,
            'equipements' => $this->equipements->map(fn ($eq) => [
                'id'      => $eq->id,
                'libelle' => $eq->libelle,
            ]),
        ];
    }
}
