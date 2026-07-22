<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('meetings', 'type_reunion')) {
            Schema::table('meetings', function (Blueprint $table) {
                $table->string('type_reunion')->nullable()->after('agenda');
            });
        }

        if (! Schema::hasColumn('meetings', 'lieu')) {
            Schema::table('meetings', function (Blueprint $table) {
                $table->string('lieu')->nullable()->after('type_reunion');
            });
        }

        if (! Schema::hasColumn('votes', 'nb_choix_autorises')) {
            Schema::table('votes', function (Blueprint $table) {
                $table->integer('nb_choix_autorises')->default(1)->after('ends_at');
            });
        }

        if (! Schema::hasColumn('votes', 'final_decision')) {
            Schema::table('votes', function (Blueprint $table) {
                $table->string('final_decision')->nullable()->after('nb_choix_autorises');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('votes', 'final_decision')) {
            Schema::table('votes', function (Blueprint $table) {
                $table->dropColumn('final_decision');
            });
        }

        if (Schema::hasColumn('votes', 'nb_choix_autorises')) {
            Schema::table('votes', function (Blueprint $table) {
                $table->dropColumn('nb_choix_autorises');
            });
        }

        if (Schema::hasColumn('meetings', 'lieu')) {
            Schema::table('meetings', function (Blueprint $table) {
                $table->dropColumn('lieu');
            });
        }

        if (Schema::hasColumn('meetings', 'type_reunion')) {
            Schema::table('meetings', function (Blueprint $table) {
                $table->dropColumn('type_reunion');
            });
        }
    }
};
