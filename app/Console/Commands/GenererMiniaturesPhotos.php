<?php

namespace App\Console\Commands;

use App\Modules\Logements\Models\Photo;
use App\Shared\Services\FileUploadService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenererMiniaturesPhotos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'photos:miniatures';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Génère les miniatures (600px, JPEG 78) pour les photos de logements existantes sans modifier les originaux';

    /**
     * Execute the console command.
     */
    public function handle(FileUploadService $fileUploadService): int
    {
        $this->info('Génération des miniatures des photos en cours...');

        $creees = 0;
        $dejaPresentes = 0;
        $introuvables = 0;
        $echecs = 0;

        $disk = Storage::disk('public');

        Photo::query()->chunkById(100, function ($photos) use ($disk, $fileUploadService, &$creees, &$dejaPresentes, &$introuvables, &$echecs) {
            foreach ($photos as $photo) {
                if (!$photo->chemin) {
                    $introuvables++;
                    continue;
                }

                $thumbPath = FileUploadService::thumbnailPath($photo->chemin);

                // Déjà présente
                if ($disk->exists($thumbPath)) {
                    $dejaPresentes++;
                    continue;
                }

                // Fichier original introuvable
                if (!$disk->exists($photo->chemin)) {
                    $introuvables++;
                    continue;
                }

                // Génération de la miniature
                $succes = $fileUploadService->createThumbnail($photo->chemin, $thumbPath, 600, 78);

                if ($succes) {
                    $creees++;
                } else {
                    $echecs++;
                }
            }
        });

        $this->info('Traitement terminé :');
        $this->line("- Créées : {$creees}");
        $this->line("- Déjà présentes : {$dejaPresentes}");
        $this->line("- Fichiers introuvables : {$introuvables}");
        $this->line("- Échecs : {$echecs}");

        return self::SUCCESS;
    }
}
