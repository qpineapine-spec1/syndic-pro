<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_providers', function (Blueprint $table) {
            if (!Schema::hasColumn('service_providers', 'expiration_alert_sent_at')) {
                $table->timestamp('expiration_alert_sent_at')->nullable()->after('alert_expiration_days');
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_providers', function (Blueprint $table) {
            if (Schema::hasColumn('service_providers', 'expiration_alert_sent_at')) {
                $table->dropColumn('expiration_alert_sent_at');
            }
        });
    }
};