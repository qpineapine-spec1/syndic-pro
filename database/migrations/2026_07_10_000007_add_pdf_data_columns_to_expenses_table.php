<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('categorie')->nullable()->after('label');
            $table->decimal('montant_mensuel', 15, 2)->nullable()->after('amount');
            $table->string('justificatif_pdf')->nullable()->after('montant_mensuel');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['categorie', 'montant_mensuel', 'justificatif_pdf']);
        });
    }
};
