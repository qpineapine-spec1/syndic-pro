<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meeting_requests', function (Blueprint $table) {
            $table->foreignId('meeting_id')->nullable()->constrained('meetings')->nullOnDelete()->after('votes_for');
            $table->timestamp('triggered_at')->nullable()->after('meeting_id');
        });
    }

    public function down(): void
    {
        Schema::table('meeting_requests', function (Blueprint $table) {
            $table->dropColumn(['meeting_id', 'triggered_at']);
        });
    }
};
