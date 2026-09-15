<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only raw check log. No native partitioning (unavailable/unmanageable on shared
        // hosting without SSH) — kept small by maintenance:run's retention DELETE
        // (config('uptik.check_retention_hours')). Dashboards read the rollup tables, never this one.
        Schema::create('check_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained()->cascadeOnDelete();
            $table->string('region');
            $table->timestamp('ts')->useCurrent();
            $table->boolean('ok');
            $table->smallInteger('status_code')->nullable();
            $table->integer('latency_ms')->nullable();
            $table->integer('dns_ms')->nullable();
            $table->integer('tcp_ms')->nullable();
            $table->integer('tls_ms')->nullable();
            $table->integer('ttfb_ms')->nullable();
            $table->string('error_class')->nullable();
            $table->text('error_msg')->nullable();
            $table->string('resolved_ip', 45)->nullable();
            $table->json('redirect_chain')->nullable();

            $table->index(['monitor_id', 'ts'], 'check_results_monitor_ts_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_results');
    }
};
