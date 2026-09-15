<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('owner');
            $table->timestamps();

            $table->unique(['user_id', 'organization_id']);
            $table->index('organization_id', 'memberships_org_idx');
        });

        DB::statement("ALTER TABLE memberships ADD CONSTRAINT memberships_role_ck CHECK (role in ('owner','admin','member','viewer'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
