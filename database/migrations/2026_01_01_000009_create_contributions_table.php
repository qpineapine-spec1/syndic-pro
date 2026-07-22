<?php

// BR-01, BR-02, BR-03
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('budget_id')->constrained()->cascadeOnDelete();
            $table->decimal('tantieme', 10, 4);
            $table->decimal('quote_part_terrain', 10, 4)->nullable();
            $table->decimal('montant_annuel', 12, 2);
            $table->decimal('montant_mensuel', 10, 2);
            $table->decimal('charges_surplus', 10, 2)->default(0);
            $table->enum('status', ['a_jour', 'en_retard', 'en_attente'])->default('en_attente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contributions');
    }
};
