<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logement_equipement', function (Blueprint $table) {
            $table->foreignId('logement_id')->constrained('logements')->cascadeOnDelete();
            $table->foreignId('equipement_id')->constrained('equipements')->cascadeOnDelete();
            $table->primary(['logement_id', 'equipement_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logement_equipement');
    }
};
