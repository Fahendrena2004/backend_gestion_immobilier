<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->unique()->constrained('locations')->cascadeOnDelete();
            $table->date('date_signature')->nullable();
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->decimal('montant_loyer', 12, 2);
            $table->decimal('montant_caution', 12, 2)->nullable();
            $table->text('conditions')->nullable();
            $table->string('chemin_pdf', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrats');
    }
};
