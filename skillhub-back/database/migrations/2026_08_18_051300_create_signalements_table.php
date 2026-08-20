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
        if (! Schema::hasTable('signalements')) {
            Schema::create('signalements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('formation_id')->constrained('formations')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->enum('motif', ['contenu_inapproprie', 'erreur_technique', 'autre']);
                $table->text('description')->nullable();
                $table->enum('statut', ['en_attente', 'traite', 'rejete'])->default('en_attente');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signalements');
    }
};
