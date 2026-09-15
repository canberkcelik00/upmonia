<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_verification_tokens', function (Blueprint $table) {
            $table->string('id', 64)->primary();
            $table->foreignId('channel_id')->constrained('alert_channels')->cascadeOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('channel_id', 'channel_verification_tokens_channel_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_verification_tokens');
    }
};
