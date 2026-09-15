<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            // Set when this channel was auto-created from a client's contact_emails
            // (see App\Services\ClientContactChannelSync), null for manually created channels.
            $table->foreignId('client_id')->nullable()->constrained()->cascadeOnDelete();
            // v1 only implements 'email' end-to-end; the rest are reserved for a future release.
            $table->enum('type', ['email', 'telegram', 'webhook', 'slack', 'discord'])->default('email');
            $table->string('name');
            // Encrypted (Laravel `encrypted` cast) JSON blob, e.g. {"email": "..."}.
            $table->text('config');
            $table->timestamp('verified_at')->nullable();
            $table->text('last_error')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->index('organization_id', 'alert_channels_org_idx');
            $table->index('client_id', 'alert_channels_client_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_channels');
    }
};
