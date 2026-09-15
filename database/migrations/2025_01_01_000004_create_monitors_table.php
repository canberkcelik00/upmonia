<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->enum('type', ['http', 'keyword', 'ssl', 'tcp_port', 'heartbeat'])->default('http');
            $table->string('url')->nullable();
            $table->string('host')->nullable();
            $table->integer('port')->nullable();
            $table->string('method')->default('GET');
            // Encrypted (Laravel `encrypted` cast) JSON blob of custom request headers.
            $table->text('headers')->nullable();
            $table->text('body')->nullable();
            $table->json('expected_status')->nullable();
            $table->string('keyword')->nullable();
            $table->string('keyword_mode')->default('present');
            $table->integer('interval_s')->default(60);
            $table->integer('timeout_ms')->default(10000);
            // v1: single vantage point (shared hosting = one server). Kept as a column for
            // forward-compatibility with a future multi-region worker, not read for branching today.
            // Actual value is set from config('uptik.default_region') at creation time, not this DB default.
            $table->string('region')->default('local');
            $table->boolean('follow_redirects')->default(true);
            $table->unsignedSmallInteger('max_redirects')->default(5);
            $table->boolean('verify_ssl')->default(true);
            $table->unsignedSmallInteger('ssl_warn_days')->default(14);
            $table->unsignedSmallInteger('confirm_threshold')->default(2);
            $table->unsignedSmallInteger('recover_threshold')->default(2);
            $table->integer('heartbeat_grace_s')->nullable();
            $table->string('heartbeat_token')->nullable()->unique();
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index('organization_id', 'monitors_org_idx');
            $table->index('client_id', 'monitors_client_idx');
        });

        // Per-type required-field validation, enforced at the DB layer as well as in
        // App\Http\Requests (defense in depth). Mirrors monitors_target_ck from the Node schema.
        DB::statement(<<<'SQL'
            ALTER TABLE monitors ADD CONSTRAINT monitors_target_ck CHECK (
                (type in ('http','keyword','ssl') and url is not null)
                or (type = 'tcp_port' and host is not null and port is not null)
                or (type = 'heartbeat' and heartbeat_token is not null)
            )
        SQL);

        DB::statement("ALTER TABLE monitors ADD CONSTRAINT monitors_keyword_mode_ck CHECK (keyword_mode in ('present','absent'))");
        DB::statement('ALTER TABLE monitors ADD CONSTRAINT monitors_interval_ck CHECK (interval_s between 60 and 86400)');
        DB::statement('ALTER TABLE monitors ADD CONSTRAINT monitors_timeout_ck CHECK (timeout_ms between 1000 and 60000)');
    }

    public function down(): void
    {
        Schema::dropIfExists('monitors');
    }
};
