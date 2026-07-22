<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('syndic_id')->constrained('syndics')->cascadeOnDelete();
            $table->integer('year');
            $table->decimal('prediction_xgboost', 10, 2)->nullable();
            $table->boolean('is_valid')->default(false);
            $table->decimal('fixed_charges_total', 15, 2)->default(0);
            $table->decimal('variable_charges_total', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
