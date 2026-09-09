<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('locataire_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('logement_id')->constrained('logements')->cascadeOnDelete();
            $table->dateTime('date_proposee')->nullable();
            $table->string('statut')->default('demandee'); // App\Shared\Enums\VisiteStatus
            $table->string('resultat', 255)->nullable();
            $table->timestamps();

            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visites');
    }
};
