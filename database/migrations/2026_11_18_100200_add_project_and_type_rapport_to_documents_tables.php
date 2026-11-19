<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Nullable : les documents personnels existants ne sont rattachés à aucun projet.
            // La colonne peut déjà exister (ajoutée par la migration de la branche
            // feature/s12-flux-projet-complet) : on ne l'ajoute alors pas une seconde fois.
            if (! Schema::hasColumn('documents', 'project_id')) {
                $table->foreignId('project_id')->nullable()->after('utilisateur_id')->constrained('projects');
            }

            if (! Schema::hasColumn('documents', 'type_rapport')) {
                $table->string('type_rapport')->nullable()->after('categorie');
            }
        });

        Schema::table('document_versions', function (Blueprint $table) {
            if (! Schema::hasColumn('document_versions', 'utilisateur_id')) {
                $table->foreignId('utilisateur_id')->nullable()->after('document_id')->constrained('users');
            }

            if (! Schema::hasColumn('document_versions', 'nom_fichier')) {
                $table->string('nom_fichier')->nullable()->after('fichier');
            }
        });
    }

    public function down(): void
    {
        Schema::table('document_versions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('utilisateur_id');
            $table->dropColumn('nom_fichier');
        });

        // documents.project_id n'est volontairement pas supprimée : elle peut provenir d'une autre
        // migration et porter des données.
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('type_rapport');
        });
    }
};
