<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_windows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['starts_at', 'ends_at'], 'maintenance_windows_range_idx');
        });

        DB::statement('ALTER TABLE maintenance_windows ADD CONSTRAINT maintenance_window_range_ck CHECK (ends_at > starts_at)');
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_windows');
    }
};
