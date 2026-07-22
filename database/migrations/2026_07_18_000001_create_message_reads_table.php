<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A message sent by the syndic can be a broadcast (owner_id = null),
 * meaning several owners share the exact same row. The previous design
 * used a single boolean `is_read` column on that row, so as soon as ONE
 * owner opened the conversation, the message flipped to "read" for
 * EVERY owner — making it vanish from their badge/preview even though
 * they had never opened it. This table tracks read state per owner
 * instead, so each recipient has their own independent read state.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at')->useCurrent();
            $table->unique(['message_id', 'owner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_reads');
    }
};