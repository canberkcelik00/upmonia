<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Replaces the Postgres `maintenance_windows.monitor_ids uuid[]` column with a real
        // pivot table (MySQL has no array type, and a pivot is more idiomatic for Eloquent anyway).
        Schema::create('maintenance_window_monitors', function (Blueprint $table) {
            $table->foreignId('maintenance_window_id')->constrained()->cascadeOnDelete();
            $table->foreignId('monitor_id')->constrained()->cascadeOnDelete();

            $table->primary(['maintenance_window_id', 'monitor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_window_monitors');
    }
};
