<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Logements\Models\Logement;
use App\Modules\Logements\Models\Photo;
use App\Modules\Logements\Models\Quartier;
use App\Modules\Logements\Models\TypeLogement;
use App\Shared\Enums\LogementStatus;
use App\Shared\Enums\ModerationStatus;
use App\Shared\Enums\UserRole;
use App\Shared\Services\FileUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_proprietaire_peut_uploader_et_supprimer_photo_avec_redimensionnement_et_miniature(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('L\'extension GD n\'est pas disponible.');
        }

        Storage::fake('public');

        $proprietaire = User::factory()->create([
            'role' => UserRole::PROPRIETAIRE,
        ]);

        $quartier = Quartier::create([
            'nom' => 'Ambalapaiso',
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
            'titre' => 'Villa avec vue',
            'description' => 'Superbe villa avec vue panoramique',
            'adresse' => 'Rue Principale',
            'superficie' => 120,
            'nombre_pieces' => 4,
            'loyer' => 500000,
            'caution' => 500000,
            'statut' => LogementStatus::DISPONIBLE,
            'statut_moderation' => ModerationStatus::APPROUVE,
        ]);

        Sanctum::actingAs($proprietaire);

        $file = UploadedFile::fake()->image('maison.jpg', 3000, 2000);

        $response = $this->postJson("/api/logements/{$logement->id}/photos", [
            'photo' => $file,
            'est_principale' => true,
        ]);

        $response->assertStatus(201);

        $photo = Photo::where('logement_id', $logement->id)->first();
        $this->assertNotNull($photo);

        $mainPath = $photo->chemin;
        $thumbPath = FileUploadService::thumbnailPath($mainPath);

        // Vérifier l'existence sur le disque fake public
        Storage::disk('public')->assertExists($mainPath);
        Storage::disk('public')->assertExists($thumbPath);

        // Vérifier les dimensions de l'image stockée (max <= 1600)
        $mainBinary = Storage::disk('public')->get($mainPath);
        $mainSize = getimagesizefromstring($mainBinary);
        $this->assertNotFalse($mainSize);
        $this->assertLessThanOrEqual(1600, max($mainSize[0], $mainSize[1]));

        // Vérifier les dimensions de la miniature (max <= 600)
        $thumbBinary = Storage::disk('public')->get($thumbPath);
        $thumbSize = getimagesizefromstring($thumbBinary);
        $this->assertNotFalse($thumbSize);
        $this->assertLessThanOrEqual(600, max($thumbSize[0], $thumbSize[1]));

        // Supprimer la photo via l'API
        $deleteResponse = $this->deleteJson("/api/logements/{$logement->id}/photos/{$photo->id}");
        $deleteResponse->assertStatus(200);

        // Vérifier que la photo en base et les deux fichiers sur le disque ont été supprimés
        $this->assertDatabaseMissing('photos', ['id' => $photo->id]);
        Storage::disk('public')->assertMissing($mainPath);
        Storage::disk('public')->assertMissing($thumbPath);
    }
}
