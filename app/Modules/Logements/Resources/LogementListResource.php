<?php

namespace App\Modules\Logements\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LogementListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $photoPrincipale = $this->photos->firstWhere('est_principale', true)
            ?? $this->photos->first();

        return [
            'id'                => $this->id,
            'titre'             => $this->titre,
            'description'       => $this->description,
            'adresse'           => $this->adresse,
            'superficie'        => $this->superficie,
            'nombre_pieces'     => $this->nombre_pieces,
            'loyer'             => $this->loyer,
            'caution'           => $this->caution,
            'statut'            => $this->statut->value,
            'statut_moderation' => $this->statut_moderation->value,
            'created_at'        => $this->created_at,
            'quartier_id'       => $this->quartier_id,
            'type_logement_id'  => $this->type_logement_id,
            'quartier'          => [
                'id'    => $this->quartier->id,
                'nom'   => $this->quartier->nom,
                'ville' => $this->quartier->ville,
            ],
            'type_logement' => [
                'id'      => $this->typeLogement->id,
                'libelle' => $this->typeLogement->libelle,
            ],
            // Exposé pour permettre au front de filtrer/afficher sans recharger
            // le détail de chaque logement.
            'equipements' => $this->whenLoaded('equipements', fn () => $this->equipements->map(fn ($eq) => [
                'id'      => $eq->id,
                'libelle' => $eq->libelle,
            ]), []),
            'photo_principale' => $photoPrincipale
                ? url('storage/' . $photoPrincipale->chemin)
                : null,
            'nombre_photos' => $this->photos->count(),
        ];
    }
}
