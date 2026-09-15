<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('channel_id')->constrained('alert_channels')->cascadeOnDelete();
            $table->string('event_type');
            $table->string('status')->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('max_attempts')->default(5);
            $table->timestamp('next_attempt_at')->useCurrent();
            $table->string('provider_msg_id')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('sent_at')->nullable();

            // Hard guarantee a channel never gets the same incident event twice.
            $table->unique(['incident_id', 'channel_id', 'event_type'], 'notifications_dedupe_idx');
            $table->index('next_attempt_at', 'notifications_retry_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
