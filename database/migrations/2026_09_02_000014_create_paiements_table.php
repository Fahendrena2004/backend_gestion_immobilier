<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facture_id')->constrained('factures')->cascadeOnDelete();
            $table->foreignId('mode_paiement_id')->constrained('modes_paiement')->restrictOnDelete();
            $table->foreignId('admin_validateur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('date_paiement')->useCurrent();
            $table->decimal('montant', 12, 2);
            $table->string('reference', 100)->nullable();
            $table->string('preuve', 255)->nullable();
            $table->string('statut')->default('declare'); // App\Shared\Enums\PaymentStatus
            $table->dateTime('date_validation')->nullable();
            $table->timestamps();

            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
