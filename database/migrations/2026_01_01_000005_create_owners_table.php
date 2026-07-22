<?php

// BR-04
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['actif', 'inactif', 'suspendu'])->default('actif');
            $table->boolean('is_tenant')->default(false);
            $table->decimal('lot_surface', 10, 2);
            $table->decimal('surface_confirmation', 10, 2);
            $table->boolean('has_mezzanine')->default(false);
            $table->decimal('mezzanine_surface', 10, 2)->nullable();
            $table->boolean('is_council_member')->default(false);
            $table->string('office_number')->nullable();
            $table->integer('floor')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owners');
    }
};
