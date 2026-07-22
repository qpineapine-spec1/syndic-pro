<?php

// BR-10
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('contract_start_date');
            $table->date('contract_end_date');
            $table->boolean('is_active')->default(true);
            $table->integer('alert_days_before_end')->default(30);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
