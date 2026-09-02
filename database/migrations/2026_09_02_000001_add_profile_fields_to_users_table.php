<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Locataire (H)
            $table->string('profession', 100)->nullable()->after('cin');
            // Propriétaire (H)
            $table->string('adresse', 200)->nullable()->after('profession');
            // Administrateur (H)
            $table->string('niveau_acces', 30)->nullable()->after('adresse');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['profession', 'adresse', 'niveau_acces']);
        });
    }
};
