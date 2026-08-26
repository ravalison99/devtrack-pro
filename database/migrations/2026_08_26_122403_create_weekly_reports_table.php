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
    Schema::create('weekly_reports', function (Blueprint $table) {
        $table->id();
        $table->foreignId('stagiaire_id')->constrained('users')->cascadeOnDelete();
        $table->unsignedTinyInteger('semaine');
        $table->string('fichier_pdf')->nullable();
        $table->enum('statut', ['soumis', 'valide', 'a_corriger'])->default('soumis');
        $table->text('commentaire_mentor')->nullable();
        $table->timestamps();

        $table->unique(['stagiaire_id', 'semaine']);
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_reports');
    }
};
