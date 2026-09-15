<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitor_channels', function (Blueprint $table) {
            $table->foreignId('monitor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('channel_id')->constrained('alert_channels')->cascadeOnDelete();
            $table->integer('delay_s')->default(0);

            $table->primary(['monitor_id', 'channel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitor_channels');
    }
};
