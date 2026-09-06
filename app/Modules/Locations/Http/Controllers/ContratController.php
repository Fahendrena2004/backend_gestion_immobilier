<?php

namespace App\Modules\Locations\Http\Controllers;

use App\Modules\Locations\Models\Contrat;
use App\Shared\Services\PdfGeneratorService;
use App\Shared\Traits\ApiResponseTrait;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ContratController
{
    use ApiResponseTrait;

    public function generatePdf(Request $request, int $id, DomPdf $pdf, PdfGeneratorService $service): JsonResponse|Response
    {
        $contrat = Contrat::with('location.logement.proprietaire', 'location.locataire')->findOrFail($id);

        $user = $request->user();
        $proprietaireId = $contrat->location->logement->proprietaire_id;
        $locataireId = $contrat->location->locataire_id;

        if ($user->id !== $proprietaireId && $user->id !== $locataireId) {
            return $this->errorResponse(
                'Vous n\'avez pas les droits pour accéder à ce contrat.',
                403
            );
        }

        if (empty($contrat->chemin_pdf)) {
            $pdfContent = $pdf->loadView('pdf.contrat', ['contrat' => $contrat])->output();

            $chemin = $service->savePdf($pdfContent, 'contrats');

            $contrat->update(['chemin_pdf' => $chemin]);
        }

        $fullPath = storage_path('app/public/' . $contrat->chemin_pdf);

        if (!file_exists($fullPath)) {
            return $this->errorResponse('Fichier PDF introuvable.', 404);
        }

        return response()->download($fullPath, 'contrat-' . $contrat->id . '.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
