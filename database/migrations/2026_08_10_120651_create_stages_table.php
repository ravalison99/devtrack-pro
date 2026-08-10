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
    Schema::create('stages', function (Blueprint $table) {
        $table->id();
        $table->foreignId('stagiaire_id')->constrained('users')->cascadeOnDelete();
        $table->foreignId('mentor_id')->constrained('users')->cascadeOnDelete();
        $table->date('date_debut');
        $table->date('date_fin');
        $table->enum('statut', ['planifie', 'en_cours', 'termine', 'annule'])->default('planifie');
        $table->enum('mode_travail', ['presentiel', 'hybride', 'teletravail'])->default('presentiel');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stages');
    }
};
