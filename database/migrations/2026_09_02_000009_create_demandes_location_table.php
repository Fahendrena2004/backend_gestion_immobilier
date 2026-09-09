<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandes_location', function (Blueprint $table) {
            $table->id();
            $table->foreignId('locataire_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('logement_id')->constrained('logements')->cascadeOnDelete();
            $table->text('message')->nullable();
            $table->string('statut')->default('en_attente'); // App\Shared\Enums\DemandeStatus
            $table->timestamps();

            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes_location');
    }
};
