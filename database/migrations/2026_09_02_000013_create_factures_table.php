<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->string('numero_facture', 40)->unique();
            $table->date('date_emission');
            $table->date('date_echeance');
            $table->decimal('montant', 12, 2);
            $table->string('periode', 20)->nullable();
            $table->string('statut')->default('impayee'); // App\Shared\Enums\FactureStatus
            $table->timestamps();

            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
};
