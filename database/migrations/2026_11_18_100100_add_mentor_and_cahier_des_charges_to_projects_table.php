<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('stage_id')->nullable()->change();
            $table->foreignId('mentor_id')->nullable()->after('stage_id')->constrained('users');
            $table->string('cahier_charges_nom')->nullable()->after('description');
            $table->string('cahier_charges_chemin')->nullable()->after('cahier_charges_nom');
            $table->unsignedBigInteger('cahier_charges_taille')->nullable()->after('cahier_charges_chemin');
        });

        // Les projets historiques gardent leur stage ; leur mentor est repris du stage.
        DB::table('projects')
            ->whereNull('mentor_id')
            ->update(['mentor_id' => DB::raw('(select mentor_id from stages where stages.id = projects.stage_id)')]);
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mentor_id');
            $table->dropColumn(['cahier_charges_nom', 'cahier_charges_chemin', 'cahier_charges_taille']);
        });
    }
};
