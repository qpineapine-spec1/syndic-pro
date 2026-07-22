<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('properties', 'reglement_fichier')) {
            Schema::table('properties', function (Blueprint $table) {
                $table->string('reglement_fichier')->nullable()->after('address');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('properties', 'reglement_fichier')) {
            Schema::table('properties', function (Blueprint $table) {
                $table->dropColumn('reglement_fichier');
            });
        }
    }
};
