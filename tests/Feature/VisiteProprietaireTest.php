<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Logements\Models\Logement;
use App\Modules\Logements\Models\Quartier;
use App\Modules\Logements\Models\TypeLogement;
use App\Modules\Visites\Models\Visite;
use App\Shared\Enums\LogementStatus;
use App\Shared\Enums\ModerationStatus;
use App\Shared\Enums\UserRole;
use App\Shared\Enums\VisiteStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VisiteProprietaireTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_proprietaire_retourne_le_nom_du_locataire_sans_son_email(): void
    {
        [$proprietaire, $locataire, $logement] = $this->creerContexteVisite();
        Visite::create([
            'locataire_id' => $locataire->id,
            'logement_id' => $logement->id,
            'statut' => VisiteStatus::DEMANDEE,
        ]);

        Sanctum::actingAs($proprietaire);

        $this->getJson('/api/visites')
            ->assertOk()
            ->assertJsonPath('data.0.locataire.name', $locataire->name)
            ->assertJsonMissingPath('data.0.locataire.email');
    }

    public function test_proposition_de_creneau_retourne_le_nom_du_locataire(): void
    {
        [$proprietaire, $locataire, $logement] = $this->creerContexteVisite();
        $visite = Visite::create([
            'locataire_id' => $locataire->id,
            'logement_id' => $logement->id,
            'statut' => VisiteStatus::DEMANDEE,
        ]);

        Sanctum::actingAs($proprietaire);

        $this->patchJson("/api/visites/{$visite->id}/status", [
            'statut' => VisiteStatus::PROPOSEE->value,
            'date_proposee' => now()->addDays(2)->toDateTimeString(),
        ])
            ->assertOk()
            ->assertJsonPath('data.locataire.name', $locataire->name);
    }

    private function creerContexteVisite(): array
    {
        $proprietaire = User::factory()->create(['role' => UserRole::PROPRIETAIRE]);
        $locataire = User::factory()->create([
            'role' => UserRole::LOCATAIRE,
            'name' => 'Locataire Test',
            'email' => 'locataire@example.com',
        ]);
        $quartier = Quartier::create([
            'nom' => 'Centre',
            'ville' => 'Fianarantsoa',
            'est_valide' => true,
        ]);
        $typeLogement = TypeLogement::create([
            'libelle' => 'Appartement',
            'est_valide' => true,
        ]);
        $logement = Logement::create([
            'proprietaire_id' => $proprietaire->id,
            'quartier_id' => $quartier->id,
            'type_logement_id' => $typeLogement->id,
            'titre' => 'Logement de test',
            'description' => 'Description de test',
            'adresse' => 'Rue du Centre',
            'superficie' => 80,
            'nombre_pieces' => 3,
            'loyer' => 300000,
            'caution' => 300000,
            'statut' => LogementStatus::DISPONIBLE,
            'statut_moderation' => ModerationStatus::APPROUVE,
        ]);

        return [$proprietaire, $locataire, $logement];
    }
}
