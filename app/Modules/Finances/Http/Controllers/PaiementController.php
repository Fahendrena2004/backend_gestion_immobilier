<?php

namespace App\Modules\Finances\Http\Controllers;

use App\Modules\Finances\Http\Requests\StorePaiementRequest;
use App\Modules\Finances\Models\Facture;
use App\Modules\Finances\Models\Paiement;
use App\Modules\Finances\Models\Quittance;
use App\Modules\Notifications\Models\Notification;
use App\Shared\Enums\FactureStatus;
use App\Shared\Enums\PaymentStatus;
use App\Shared\Services\FileUploadService;
use App\Shared\Services\PdfGeneratorService;
use App\Shared\Traits\ApiResponseTrait;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaiementController
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Paiement::with(['facture.location.logement', 'facture.location.locataire', 'modePaiement', 'quittance']);

        if ($user->isLocataire()) {
            $query->whereHas('facture.location', fn ($q) => $q->where('locataire_id', $user->id));
        } elseif ($user->isProprietaire()) {
            $query->whereHas('facture.location.logement', fn ($q) => $q->where('proprietaire_id', $user->id));
        }

        if ($request->filled('statut') && $statut = PaymentStatus::tryFrom($request->statut)) {
            $query->where('statut', $statut);
        }

        if ($request->filled('location_id')) {
            $query->whereHas('facture', fn ($q) => $q->where('location_id', $request->location_id));
        }

        $paiements = $query->orderByDesc('created_at')->paginate(15);

        return $this->successResponse($paiements);
    }

    public function store(StorePaiementRequest $request, FileUploadService $uploadService): JsonResponse
    {
        $user = $request->user();

        $facture = Facture::with('location')->findOrFail($request->facture_id);

        if ($facture->location->locataire_id !== $user->id) {
            return $this->errorResponse(
                'Cette facture n\'appartient pas à votre compte.',
                403
            );
        }

        if ($facture->statut !== FactureStatus::IMPAYEE && $facture->statut !== FactureStatus::EN_RETARD) {
            return $this->errorResponse(
                'Cette facture ne peut plus faire l\'objet d\'un paiement.',
                422
            );
        }

        $preuvePath = $uploadService->upload($request->file('preuve'), 'preuves');

        $paiement = Paiement::create([
            'facture_id'       => $facture->id,
            'mode_paiement_id' => $request->mode_paiement_id,
            'montant'          => $request->montant,
            'reference'        => $request->reference,
            'preuve'           => $preuvePath,
            'statut'           => PaymentStatus::DECLARE,
            'date_paiement'    => now(),
        ]);

        return $this->successResponse($paiement->load('facture'), 'Paiement déclaré avec succès', 201);
    }

    public function valider(Request $request, int $id, DomPdf $pdf, PdfGeneratorService $pdfService): JsonResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'string', 'in:valider,rejeter'],
        ]);

        $decision = $validated['decision'];
        $admin = $request->user();

        $result = DB::transaction(function () use ($id, $decision, $admin, $pdf, $pdfService) {
            $paiement = Paiement::lockForUpdate()->findOrFail($id);

            if ($paiement->statut !== PaymentStatus::DECLARE) {
                return $this->errorResponse(
                    'Ce paiement a déjà été traité.',
                    422
                );
            }

            if ($decision === 'valider') {
                $paiement->update([
                    'statut'               => PaymentStatus::VALIDE,
                    'admin_validateur_id'  => $admin->id,
                    'date_validation'      => now(),
                ]);

                $paiement->facture->update(['statut' => FactureStatus::PAYEE]);

                $numero = $this->genererNumeroQuittance();

                $quittance = Quittance::create([
                    'paiement_id'       => $paiement->id,
                    'numero_quittance'  => $numero,
                    'date_emission'     => now()->toDateString(),
                ]);

                $pdfContent = $pdf->loadView('pdf.quittance', [
                    'quittance' => $quittance->load('paiement.facture.location.locataire'),
                ])->output();

                $chemin = $pdfService->savePdf($pdfContent, 'quittances');
                $quittance->update(['chemin_pdf' => $chemin]);

                Notification::create([
                    'user_id' => $paiement->facture->location->locataire_id,
                    'titre'   => 'Paiement validé',
                    'contenu' => "Votre paiement de {$paiement->montant} Ar pour la facture {$paiement->facture->numero_facture} a été validé. Quittance n°{$numero}.",
                    'type'    => 'paiement_valide',
                ]);

                return $this->successResponse(
                    $paiement->load(['quittance', 'facture']),
                    'Paiement validé avec succès'
                );
            }

            // rejeter
            $paiement->update([
                'statut'              => PaymentStatus::REJETE,
                'admin_validateur_id' => $admin->id,
                'date_validation'     => now(),
            ]);

            Notification::create([
                'user_id' => $paiement->facture->location->locataire_id,
                'titre'   => 'Paiement rejeté',
                'contenu' => "Votre paiement de {$paiement->montant} Ar pour la facture {$paiement->facture->numero_facture} a été rejeté. Veuillez contacter l'administration.",
                'type'    => 'paiement_rejete',
            ]);

            return $this->successResponse(
                $paiement->load('facture'),
                'Paiement rejeté'
            );
        });

        return $result;
    }

    private function genererNumeroQuittance(): string
    {
        $year = date('Y');
        $prefix = "QT-{$year}-";

        $max = Quittance::where('numero_quittance', 'like', "{$prefix}%")
            ->lockForUpdate()
            ->value(DB::raw("CAST(SUBSTRING(numero_quittance, " . (strlen($prefix) + 1) . ") AS UNSIGNED)"));

        $sequence = ($max ?? 0) + 1;

        return sprintf('QT-%s-%06d', $year, $sequence);
    }
}
