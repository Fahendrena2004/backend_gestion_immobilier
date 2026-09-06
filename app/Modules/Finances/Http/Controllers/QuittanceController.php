<?php

namespace App\Modules\Finances\Http\Controllers;

use App\Modules\Finances\Models\Quittance;
use App\Shared\Services\PdfGeneratorService;
use App\Shared\Traits\ApiResponseTrait;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QuittanceController
{
    use ApiResponseTrait;

    public function downloadPdf(Request $request, int $id, DomPdf $pdf, PdfGeneratorService $pdfService): JsonResponse|BinaryFileResponse
    {
        $quittance = Quittance::with('paiement.facture.location.locataire', 'paiement.facture.location.logement.proprietaire')
            ->findOrFail($id);

        $user = $request->user();
        $locataireId = $quittance->paiement->facture->location->locataire_id;
        $proprietaireId = $quittance->paiement->facture->location->logement->proprietaire_id;

        if ($user->role->value !== 'admin' && $user->id !== $locataireId && $user->id !== $proprietaireId) {
            return $this->errorResponse(
                'Vous n\'avez pas les droits pour accéder à cette quittance.',
                403
            );
        }

        if (empty($quittance->chemin_pdf)) {
            $pdfContent = $pdf->loadView('pdf.quittance', ['quittance' => $quittance])->output();

            $chemin = $pdfService->savePdf($pdfContent, 'quittances');

            $quittance->update(['chemin_pdf' => $chemin]);
        }

        $fullPath = storage_path('app/public/' . $quittance->chemin_pdf);

        if (!file_exists($fullPath)) {
            return $this->errorResponse('Fichier PDF introuvable.', 404);
        }

        return response()->download($fullPath, 'quittance-' . $quittance->numero_quittance . '.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
