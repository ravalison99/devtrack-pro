<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            // Auteur du dépôt. Nullable : pièces jointes historiques dont l'auteur n'a pas été enregistré.
            $table->foreignId('utilisateur_id')->nullable()->after('task_id')->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('utilisateur_id');
        });
    }
};
