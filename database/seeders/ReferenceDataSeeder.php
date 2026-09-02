<?php

namespace Database\Seeders;

use App\Modules\Logements\Models\Equipement;
use App\Modules\Logements\Models\Quartier;
use App\Modules\Logements\Models\TypeLogement;
use App\Modules\Finances\Models\ModePaiement;
use Illuminate\Database\Seeder;

class ReferenceDataSeeder extends Seeder
{
    /**
     * Alimente les tables de référence (quartiers de Fianarantsoa, types de
     * logement, équipements, modes de paiement) nécessaires au fonctionnement
     * de la plateforme. Gérées ensuite par l'administrateur (US-A-06, US-A-07).
     */
    public function run(): void
    {
        $quartiers = [
            'Andrainjato', 'Ambalapoaka', 'Ambalakisoa', 'Analakely Fianarantsoa',
            'Antarandolo', 'Isada', 'Tsianolondroa', 'Ampasambazaha',
            'Andrainjato Sud', 'Anjoma', 'Ambatolahikisoa', 'Antarandolo Ambony',
        ];
        foreach ($quartiers as $nom) {
            Quartier::firstOrCreate(['nom' => $nom], ['ville' => 'Fianarantsoa']);
        }

        $types = ['Studio', 'Appartement', 'Villa', 'Chambre', 'Maison individuelle', 'Bureau / Local commercial'];
        foreach ($types as $libelle) {
            TypeLogement::firstOrCreate(['libelle' => $libelle]);
        }

        $equipements = [
            'Eau courante', 'Électricité', 'Wifi / Internet', 'Meublé',
            'Cuisine équipée', 'Parking', 'Sécurité / Gardiennage', 'Balcon',
            'Climatisation', 'Eau chaude',
        ];
        foreach ($equipements as $libelle) {
            Equipement::firstOrCreate(['libelle' => $libelle]);
        }

        $modes = ['MVola', 'Airtel Money', 'Orange Money', 'Espèces', 'Virement bancaire'];
        foreach ($modes as $libelle) {
            ModePaiement::firstOrCreate(['libelle' => $libelle], ['actif' => true]);
        }
    }
}
