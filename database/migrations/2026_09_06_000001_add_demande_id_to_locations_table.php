<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->foreignId('demande_id')->nullable()->after('logement_id')->constrained('demandes_location')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropForeign(['demande_id']);
            $table->dropColumn('demande_id');
        });
    }
};
