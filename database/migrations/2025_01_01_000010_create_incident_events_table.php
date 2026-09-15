<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->timestamp('ts')->useCurrent();
            $table->string('type');
            $table->json('payload')->nullable();

            $table->index(['incident_id', 'ts'], 'incident_events_incident_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_events');
    }
};
