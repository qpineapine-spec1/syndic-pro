<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notifications')) return;

        DB::statement('ALTER TABLE notifications MODIFY owner_id BIGINT UNSIGNED NULL');

        Schema::table('notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('notifications', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->after('owner_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('notifications')) return;

        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
        });

        DB::statement('ALTER TABLE notifications MODIFY owner_id BIGINT UNSIGNED NOT NULL');
    }
};
