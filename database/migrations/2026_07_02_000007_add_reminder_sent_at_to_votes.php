<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('votes')) return;

        Schema::table('votes', function (Blueprint $table) {
            if (!Schema::hasColumn('votes', 'reminder_sent_at')) {
                $table->timestamp('reminder_sent_at')->nullable()->after('ends_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('votes')) return;

        Schema::table('votes', function (Blueprint $table) {
            if (Schema::hasColumn('votes', 'reminder_sent_at')) {
                $table->dropColumn('reminder_sent_at');
            }
        });
    }
};
