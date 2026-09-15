<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Added after incidents exists. The Node/Postgres schema left this as an informal,
        // unenforced reference; enforcing it here is free correctness MySQL gives us for nothing.
        Schema::table('monitor_states', function (Blueprint $table) {
            $table->foreign('current_incident_id')->references('id')->on('incidents')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('monitor_states', function (Blueprint $table) {
            $table->dropForeign(['current_incident_id']);
        });
    }
};
