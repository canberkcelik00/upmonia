<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('check_results', function (Blueprint $table) {
            // Segment-log compaction (see CheckResultApplier::apply()): a row now represents
            // a run of consecutive checks with the same (ok, error_class) outcome rather than
            // one row per check. sample_count counts how many checks the row represents;
            // updated_at is when the segment's outcome was last reconfirmed. `ts` keeps its
            // original meaning: when the segment started. Deliberately no application-level
            // Eloquent timestamp management (CheckResult keeps $timestamps = false) — both
            // columns are set explicitly in CheckResultApplier so there is never ambiguity
            // about which write path touched them.
            $table->unsignedInteger('sample_count')->default(1)->after('redirect_chain');
            $table->timestamp('updated_at')->useCurrent()->after('sample_count');
        });
    }

    public function down(): void
    {
        Schema::table('check_results', function (Blueprint $table) {
            $table->dropColumn(['sample_count', 'updated_at']);
        });
    }
};
