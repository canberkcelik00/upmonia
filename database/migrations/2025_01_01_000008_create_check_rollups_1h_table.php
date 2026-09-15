<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('check_rollups_1h', function (Blueprint $table) {
            $table->foreignId('monitor_id')->constrained()->cascadeOnDelete();
            $table->string('region');
            $table->timestamp('bucket');
            $table->integer('ok_n')->default(0);
            $table->integer('fail_n')->default(0);
            $table->integer('p50')->nullable();
            $table->integer('p95')->nullable();
            $table->integer('max_ms')->nullable();

            $table->primary(['monitor_id', 'region', 'bucket']);
            $table->index('bucket', 'check_rollups_1h_bucket_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_rollups_1h');
    }
};
