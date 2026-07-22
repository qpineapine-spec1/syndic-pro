<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            if (!Schema::hasColumn('complaints', 'category')) {
                $table->string('category')->nullable()->after('priority');
            }
            if (!Schema::hasColumn('complaints', 'date')) {
                $table->date('date')->nullable()->after('category');
            }
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            if (Schema::hasColumn('complaints', 'category')) {
                $table->dropColumn('category');
            }
            if (Schema::hasColumn('complaints', 'date')) {
                $table->dropColumn('date');
            }
        });
    }
};
