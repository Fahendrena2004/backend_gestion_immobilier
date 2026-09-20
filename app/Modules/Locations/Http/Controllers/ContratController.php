<?php

namespace App\Modules\Locations\Http\Controllers;

use App\Modules\Locations\Models\Location;
use App\Shared\Services\PdfGeneratorService;
use App\Shared\Traits\ApiResponseTrait;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ContratController
{
    use ApiResponseTrait;

    /**
     * Télécharge le contrat d'une location.
     *
     * L'identifiant de la route est celui de la LOCATION (la route est
     * /locations/{id}/contrat-pdf) : le contrat est retrouvé via la relation.
     */
    public function generatePdf(
        Request $request,
        int $id,
        DomPdf $pdf,
        PdfGeneratorService $service
    ): JsonResponse|BinaryFileResponse {
        $location = Location::with('contrat', 'logement.proprietaire', 'locataire')
            ->findOrFail($id);

        $user = $request->user();

        if ($user->id !== $location->logement->proprietaire_id
            && $user->id !== $location->locataire_id
            && !$user->isAdmin()) {
            return $this->errorResponse(
                'Vous n\'avez pas les droits pour accéder à ce contrat.',
                403
            );
        }

        $contrat = $location->contrat;

        if (!$contrat) {
            return $this->errorResponse('Aucun contrat n\'est rattaché à cette location.', 404);
        }

        // Le contrat est chargé avec sa location pour la vue PDF.
        $contrat->setRelation('location', $location);

        if (empty($contrat->chemin_pdf)) {
            $pdfContent = $pdf->loadView('pdf.contrat', ['contrat' => $contrat])->output();

            $contrat->update(['chemin_pdf' => $service->savePdf($pdfContent, 'contrats')]);
        }

        $fullPath = storage_path('app/public/' . $contrat->chemin_pdf);

        if (!file_exists($fullPath)) {
            // Le fichier a disparu du stockage : on le régénère.
            $pdfContent = $pdf->loadView('pdf.contrat', ['contrat' => $contrat])->output();

            $contrat->update(['chemin_pdf' => $service->savePdf($pdfContent, 'contrats')]);

            $fullPath = storage_path('app/public/' . $contrat->chemin_pdf);
        }

        return response()->download($fullPath, 'contrat-' . $contrat->id . '.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
