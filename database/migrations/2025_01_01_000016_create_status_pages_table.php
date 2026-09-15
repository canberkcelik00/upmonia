<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('brand_color')->nullable();
            $table->string('logo_url')->nullable();
            $table->boolean('enabled')->default(false);
            $table->boolean('show_history')->default(true);
            $table->boolean('indexable')->default(false);
            $table->timestamps();

            $table->index('organization_id', 'status_pages_org_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_pages');
    }
};
