<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitor_states', function (Blueprint $table) {
            $table->foreignId('monitor_id')->primary()->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'up', 'suspect', 'down', 'recovering', 'paused'])->default('pending');
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('next_check_at')->useCurrent();
            $table->unsignedSmallInteger('consecutive_fails')->default(0);
            $table->unsignedSmallInteger('consecutive_ok')->default(0);
            $table->integer('last_latency_ms')->nullable();
            $table->string('last_error_class')->nullable();
            $table->text('last_error_msg')->nullable();
            $table->smallInteger('last_status_code')->nullable();
            // Recent confirmed-transition timestamps, used for flap detection (last 10 minutes).
            $table->json('recent_transitions')->nullable();
            $table->boolean('flapping')->default(false);
            $table->timestamp('cert_expires_at')->nullable();
            $table->string('cert_issuer')->nullable();
            $table->timestamp('last_heartbeat_at')->nullable();
            // FK added in a later migration, once the incidents table exists.
            $table->unsignedBigInteger('current_incident_id')->nullable();
            // Set by the "check now" button; probe:run prioritizes these on its next tick.
            $table->timestamp('check_requested_at')->nullable();
            // Cron-tick lease: prevents a slow-running probe:run from double-picking a monitor
            // if a previous invocation overruns (defense in depth alongside withoutOverlapping()).
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index(['status', 'next_check_at'], 'monitor_states_due_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitor_states');
    }
};
