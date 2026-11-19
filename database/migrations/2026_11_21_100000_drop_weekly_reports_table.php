<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les rapports hebdomadaires sont désormais déposés directement dans les détails du projet
 * (documents de projet avec type de rapport) : la fonctionnalité /reports est supprimée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('weekly_reports');
    }

    public function down(): void
    {
        Schema::create('weekly_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stagiaire_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('semaine');
            $table->text('contenu')->nullable();
            $table->string('fichier_pdf')->nullable();
            $table->enum('statut', ['soumis', 'valide', 'a_corriger'])->default('soumis');
            $table->text('commentaire_mentor')->nullable();
            $table->timestamps();

            $table->unique(['stagiaire_id', 'semaine']);
        });
    }
};
