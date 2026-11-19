<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groupes', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->foreignId('mentor_id')->constrained('users');
            // Le chef de groupe est un stagiaire désigné : pas de nouveau rôle, une simple référence.
            $table->foreignId('chef_id')->constrained('users');
            // Un projet ne peut être affecté qu'à un seul groupe.
            $table->foreignId('project_id')->unique()->constrained('projects');
            $table->timestamps();
        });

        Schema::create('groupe_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('groupe_id')->constrained('groupes')->cascadeOnDelete();
            // Un stagiaire ne peut appartenir qu'à un seul groupe.
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groupe_user');
        Schema::dropIfExists('groupes');
    }
};
