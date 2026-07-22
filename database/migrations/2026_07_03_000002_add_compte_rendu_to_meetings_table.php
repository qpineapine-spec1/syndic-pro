<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('meetings', 'compte_rendu')) {
            Schema::table('meetings', function (Blueprint $table) {
                $table->string('compte_rendu')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('meetings', 'compte_rendu')) {
            Schema::table('meetings', function (Blueprint $table) {
                $table->dropColumn('compte_rendu');
            });
        }
    }
};
