<?php

namespace Tests\Unit;

use App\Modules\Finances\Models\Facture;
use App\Modules\Finances\Models\Paiement;
use App\Shared\Enums\FactureStatus;
use App\Shared\Enums\PaymentStatus;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Schema;

class FactureSoldeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Schéma minimal sur sqlite :memory: — aucune migration MySQL,
        // aucune donnée de la base de développement touchée.
        Schema::create('factures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('location_id');
            $table->string('numero_facture', 40)->unique();
            $table->date('date_emission');
            $table->date('date_echeance');
            $table->decimal('montant', 12, 2);
            $table->string('periode', 20)->nullable();
            $table->string('statut')->default('impayee');
            $table->timestamps();
        });

        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('facture_id');
            $table->unsignedBigInteger('mode_paiement_id');
            $table->unsignedBigInteger('admin_validateur_id')->nullable();
            $table->dateTime('date_paiement')->useCurrent();
            $table->decimal('montant', 12, 2);
            $table->string('reference', 100)->nullable();
            $table->string('preuve', 255)->nullable();
            $table->string('statut')->default('declare');
            $table->dateTime('date_validation')->nullable();
            $table->timestamps();
        });
    }

    private function creerFacture(float $montant, FactureStatus $statut = FactureStatus::IMPAYEE): Facture
    {
        return Facture::create([
            'location_id'    => 1,
            'numero_facture' => 'FAC-TEST-' . uniqid(),
            'date_emission'  => '2026-10-01',
            'date_echeance'  => '2026-10-31',
            'montant'        => $montant,
            'statut'         => $statut,
        ]);
    }

    private function creerPaiement(Facture $facture, float $montant, PaymentStatus $statut): Paiement
    {
        return Paiement::create([
            'facture_id'       => $facture->id,
            'mode_paiement_id' => 1,
            'montant'          => $montant,
            'statut'           => $statut,
            'date_paiement'    => now(),
        ]);
    }

    /**
     * Reproduit la décision du contrôleur : le statut payee n'est appliqué
     * que lorsque le cumul des paiements validés couvre la facture.
     */
    private function appliquerValidationCommeControleur(Paiement $paiement): void
    {
        $paiement->update([
            'statut'          => PaymentStatus::VALIDE,
            'date_validation' => now(),
        ]);

        $facture = $paiement->facture()->firstOrFail();

        if ($facture->estEntierementSoldee()) {
            $facture->update(['statut' => FactureStatus::PAYEE]);
        }
    }

    public function test_paiement_inferieur_au_solde_ne_paye_pas_la_facture(): void
    {
        $facture = $this->creerFacture(500000);
        $paiement = $this->creerPaiement($facture, 50000, PaymentStatus::DECLARE);

        $this->appliquerValidationCommeControleur($paiement);

        $this->assertEqualsWithDelta(50000, (float) $facture->refresh()->cumulPaiementsValides(), 0.01);
        $this->assertFalse($facture->estEntierementSoldee());
        $this->assertSame(FactureStatus::IMPAYEE, $facture->statut);
    }

    public function test_paiement_couvrant_exactement_le_montant_paye_la_facture(): void
    {
        $facture = $this->creerFacture(500000);
        $paiement = $this->creerPaiement($facture, 500000, PaymentStatus::DECLARE);

        $this->appliquerValidationCommeControleur($paiement);

        $this->assertSame(FactureStatus::PAYEE, $facture->refresh()->statut);
    }

    public function test_paiements_cumules_couvrant_la_facture_la_payent(): void
    {
        $facture = $this->creerFacture(500000);
        $this->creerPaiement($facture, 200000, PaymentStatus::VALIDE);
        $this->creerPaiement($facture, 250000, PaymentStatus::VALIDE);
        $paiement = $this->creerPaiement($facture, 50000, PaymentStatus::DECLARE);

        $this->appliquerValidationCommeControleur($paiement);

        $this->assertEqualsWithDelta(500000, (float) $facture->refresh()->cumulPaiementsValides(), 0.01);
        $this->assertSame(FactureStatus::PAYEE, $facture->statut);
    }

    public function test_paiement_rejete_exclu_du_cumul(): void
    {
        $facture = $this->creerFacture(500000);
        $this->creerPaiement($facture, 500000, PaymentStatus::REJETE);
        $paiement = $this->creerPaiement($facture, 50000, PaymentStatus::DECLARE);

        $this->appliquerValidationCommeControleur($paiement);

        $this->assertEqualsWithDelta(50000, (float) $facture->refresh()->cumulPaiementsValides(), 0.01);
        $this->assertSame(FactureStatus::IMPAYEE, $facture->statut);
    }

    public function test_paiement_declare_non_valide_exclu_du_cumul(): void
    {
        $facture = $this->creerFacture(500000);
        $this->creerPaiement($facture, 400000, PaymentStatus::DECLARE);
        $this->creerPaiement($facture, 100000, PaymentStatus::VALIDE);

        $this->assertEqualsWithDelta(100000, (float) $facture->cumulPaiementsValides(), 0.01);
        $this->assertFalse($facture->estEntierementSoldee());
    }

    public function test_validations_sequentielles_couvrant_la_facture_la_payent(): void
    {
        // Simule l'issue de deux validations concurrentes : la deuxième
        // observe le cumul des deux paiements une fois le premier commité.
        $facture = $this->creerFacture(500000);
        $premier = $this->creerPaiement($facture, 300000, PaymentStatus::DECLARE);
        $second = $this->creerPaiement($facture, 200000, PaymentStatus::DECLARE);

        $this->appliquerValidationCommeControleur($premier);
        $this->assertSame(FactureStatus::IMPAYEE, $facture->refresh()->statut);

        $this->appliquerValidationCommeControleur($second);
        $this->assertSame(FactureStatus::PAYEE, $facture->refresh()->statut);
    }

    public function test_paiement_excedentaire_paye_la_facture(): void
    {
        $facture = $this->creerFacture(500000);
        $paiement = $this->creerPaiement($facture, 550000, PaymentStatus::DECLARE);

        $this->appliquerValidationCommeControleur($paiement);

        $this->assertEqualsWithDelta(550000, (float) $facture->refresh()->cumulPaiementsValides(), 0.01);
        $this->assertSame(FactureStatus::PAYEE, $facture->statut);
    }

    public function test_cumul_ignore_les_paiements_rejetes_et_declares(): void
    {
        $facture = $this->creerFacture(500000);
        $this->creerPaiement($facture, 100000, PaymentStatus::VALIDE);
        $this->creerPaiement($facture, 200000, PaymentStatus::REJETE);
        $this->creerPaiement($facture, 300000, PaymentStatus::DECLARE);

        $this->assertEqualsWithDelta(100000, (float) $facture->cumulPaiementsValides(), 0.01);
    }
}
