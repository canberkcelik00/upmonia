<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('monitor_id')->constrained()->cascadeOnDelete();
            $table->enum('state', ['open', 'resolved'])->default('open');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();
            $table->integer('duration_s')->nullable();
            $table->string('cause_class')->nullable();
            $table->text('cause_detail')->nullable();
            $table->boolean('flapping')->default(false);
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->boolean('notify_pending')->default(true);
            $table->timestamp('created_at')->useCurrent();
            // MySQL has no partial/filtered index, so "one open incident per monitor" (Postgres:
            // a partial unique index `where resolved_at is null`) is emulated with a plain
            // nullable column the app sets to monitor_id while open and NULLs out on resolve
            // (App\Models\Incident::open()/resolve()), backed by a normal unique index — MySQL
            // unique indexes permit any number of NULLs, so resolved incidents don't collide.
            // (A DB-generated STORED column was tried first but InnoDB refuses to create a
            // generated column that reads a foreign-keyed column once a table has 2+ FKs —
            // reproducible MySQL 8.0.43 limitation, not fixable from the migration side.)
            $table->unsignedBigInteger('open_marker')->nullable()->unique('incidents_one_open_per_monitor');

            $table->index(['organization_id', 'started_at'], 'incidents_org_started_idx');
            $table->index('notify_pending', 'incidents_notify_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
