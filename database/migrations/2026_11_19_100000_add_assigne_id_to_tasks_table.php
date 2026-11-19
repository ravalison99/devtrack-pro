<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Stagiaire à qui la tâche est confiée. Nullable : tâches historiques sans assigné.
            $table->foreignId('assigne_id')->nullable()->after('project_id')->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigne_id');
        });
    }
};
