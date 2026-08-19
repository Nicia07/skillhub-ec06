<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('inscriptions', function (Blueprint $table) {
        $table->id();
        
        // Clés étrangères
        $table->foreignId('id_apprenant')->constrained('users')->onDelete('cascade');
        $table->foreignId('id_formation')->constrained('formations')->onDelete('cascade');
        
        // Statut de la formation
        $table->enum('status', ['en cours', 'terminée'])->default('en cours');
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscriptions');
    }
};
