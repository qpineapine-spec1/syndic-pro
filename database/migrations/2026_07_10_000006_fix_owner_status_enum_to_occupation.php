<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // First, update existing data: 'actif' -> 'proprietaire', 'inactif' -> 'locataire', 'suspendu' -> 'proprietaire'
        DB::table('owners')
            ->where('status', 'actif')
            ->update(['status' => 'proprietaire']);
        
        DB::table('owners')
            ->where('status', 'inactif')
            ->update(['status' => 'locataire']);
        
        DB::table('owners')
            ->where('status', 'suspendu')
            ->update(['status' => 'proprietaire']);

        // Now modify the column to change the enum values
        // For MySQL, we need to modify the enum definition
        Schema::table('owners', function (Blueprint $table) {
            $table->enum('status', ['proprietaire', 'locataire'])->change();
        });
    }

    public function down(): void
    {
        // Reverse the data transformation
        DB::table('owners')
            ->where('status', 'proprietaire')
            ->update(['status' => 'actif']);
        
        DB::table('owners')
            ->where('status', 'locataire')
            ->update(['status' => 'inactif']);

        // Revert the column definition
        Schema::table('owners', function (Blueprint $table) {
            $table->enum('status', ['actif', 'inactif', 'suspendu'])->change();
        });
    }
};
