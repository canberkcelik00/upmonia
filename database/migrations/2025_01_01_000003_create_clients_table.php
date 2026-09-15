<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('logo_url')->nullable();
            $table->string('brand_color')->nullable();
            $table->json('contact_emails')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('organization_id', 'clients_org_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
