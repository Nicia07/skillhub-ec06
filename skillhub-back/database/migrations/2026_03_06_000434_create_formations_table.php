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
        Schema::create('formations', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('duration'); // durée en heures
            $table->enum('level', ['beginner', 'intermediate', 'advanced']);

            // Champs additionnels (hors périmètre du sujet EC04, utilisés par le catalogue apprenant)
            $table->string('photo')->nullable();
            $table->string('ville', 100)->nullable();
            $table->string('categorie', 100)->nullable();

            // Clé étrangère : relie la formation à son formateur (table users)
            // onDelete('cascade') supprime les formations si le formateur supprime son compte
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('formations');
    }
};
