<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Explicit opt-in join: a status page never implicitly shows "all monitors".
        Schema::create('status_page_monitors', function (Blueprint $table) {
            $table->foreignId('status_page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('monitor_id')->constrained()->cascadeOnDelete();
            $table->string('display_name')->nullable();
            $table->integer('sort_order')->default(0);

            $table->primary(['status_page_id', 'monitor_id']);
            $table->index('monitor_id', 'status_page_monitors_monitor_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_page_monitors');
    }
};
