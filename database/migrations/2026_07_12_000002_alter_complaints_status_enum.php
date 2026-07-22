<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('complaints')
            ->whereIn('status', ['open', 'pending', 'closed', 'new', 'nouvelle_reclamation', 'cloturee', ''])
            ->update(['status' => 'nouvelle']);

        DB::statement("ALTER TABLE complaints MODIFY COLUMN status VARCHAR(255) NOT NULL DEFAULT 'nouvelle'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE complaints MODIFY COLUMN status VARCHAR(255) NOT NULL DEFAULT 'nouvelle'");
    }
};
