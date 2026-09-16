<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitor_states', function (Blueprint $table) {
            // Covers ProbeRun::leaseDue()'s WHERE clause alongside (not replacing)
            // monitor_states_due_idx (status, next_check_at). Column order:
            //   1. status        - matches the existing index; filters out 'paused' rows.
            //   2. locked_until  - NULL for the overwhelming majority of rows (only rows
            //      currently leased by an in-flight probe:run have it set); InnoDB sorts
            //      NULL first in an ascending index, so "locked_until IS NULL OR
            //      locked_until < now()" resolves as one contiguous range scan from the
            //      start of the index instead of a full scan.
            //   3. next_check_at - supports both the due-time filter and the common-case
            //      ORDER BY next_check_at (when check_requested_at IS NULL).
            // check_requested_at is deliberately NOT part of this index: it's non-null only
            // for the rare manual "check now" case, and MySQL can satisfy that branch via a
            // cheap scan of the (normally tiny) locked-open row set without a dedicated index.
            $table->index(['status', 'locked_until', 'next_check_at'], 'monitor_states_due_lock_idx');
        });
    }

    public function down(): void
    {
        Schema::table('monitor_states', function (Blueprint $table) {
            $table->dropIndex('monitor_states_due_lock_idx');
        });
    }
};
