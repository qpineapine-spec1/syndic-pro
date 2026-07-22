<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE owners MODIFY lot_surface DECIMAL(8,2) NULL');
        DB::statement('ALTER TABLE owners MODIFY surface_confirmation DECIMAL(8,2) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE owners MODIFY lot_surface DECIMAL(5,2) NULL');
        DB::statement('ALTER TABLE owners MODIFY surface_confirmation DECIMAL(5,2) NULL');
    }
};
