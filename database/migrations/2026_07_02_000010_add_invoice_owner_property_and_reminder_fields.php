<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('invoices', 'owner_id')) {
            DB::statement('ALTER TABLE invoices ADD COLUMN owner_id INTEGER NULL');
        }

        if (!Schema::hasColumn('invoices', 'property_id')) {
            DB::statement('ALTER TABLE invoices ADD COLUMN property_id INTEGER NULL');
        }

        if (!Schema::hasColumn('invoices', 'reminder_sent_at')) {
            DB::statement('ALTER TABLE invoices ADD COLUMN reminder_sent_at TIMESTAMP NULL');
        }
    }

    public function down(): void
    {
        Schema::table('invoices', function ($table) {
            if (Schema::hasColumn('invoices', 'reminder_sent_at')) {
                $table->dropColumn('reminder_sent_at');
            }
            if (Schema::hasColumn('invoices', 'property_id')) {
                $table->dropColumn('property_id');
            }
            if (Schema::hasColumn('invoices', 'owner_id')) {
                $table->dropColumn('owner_id');
            }
        });
    }
};
