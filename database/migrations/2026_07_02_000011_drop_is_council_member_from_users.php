<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'is_council_member')) {
            DB::statement('ALTER TABLE users DROP COLUMN is_council_member');
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('users', 'is_council_member')) {
            DB::statement('ALTER TABLE users ADD COLUMN is_council_member BOOLEAN NOT NULL DEFAULT 0');
        }
    }
};
