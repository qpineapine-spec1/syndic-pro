<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_invitations', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash')->unique();
            $table->timestamp('expires_at');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_invitations');
    }
};
