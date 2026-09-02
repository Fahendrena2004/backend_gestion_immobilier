<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proprietaire_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('quartier_id')->constrained('quartiers')->restrictOnDelete();
            $table->foreignId('type_logement_id')->constrained('types_logement')->restrictOnDelete();
            $table->string('titre', 150);
            $table->text('description')->nullable();
            $table->string('adresse', 200)->nullable();
            $table->decimal('superficie', 7, 2)->nullable();
            $table->integer('nombre_pieces')->nullable();
            $table->decimal('loyer', 12, 2);
            $table->decimal('caution', 12, 2)->nullable();
            $table->string('statut')->default('disponible'); // App\Shared\Enums\LogementStatus
            $table->string('statut_moderation')->default('en_attente'); // App\Shared\Enums\ModerationStatus
            $table->timestamps();

            $table->index('statut');
            $table->index('statut_moderation');
            $table->index('loyer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logements');
    }
};
