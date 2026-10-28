<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quartiers', function (Blueprint $table) {
            $table->boolean('est_valide')->default(true);
        });

        Schema::table('types_logement', function (Blueprint $table) {
            $table->boolean('est_valide')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('quartiers', function (Blueprint $table) {
            $table->dropColumn('est_valide');
        });

        Schema::table('types_logement', function (Blueprint $table) {
            $table->dropColumn('est_valide');
        });
    }
};
