<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute une colonne 'validated_at' qui trace le moment où le
     * copropriétaire a validé la résolution de sa réclamation (bouton
     * "Valider la réclamation" côté copropriétaire).
     */
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            if (! Schema::hasColumn('complaints', 'validated_at')) {
                $table->timestamp('validated_at')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            if (Schema::hasColumn('complaints', 'validated_at')) {
                $table->dropColumn('validated_at');
            }
        });
    }
};