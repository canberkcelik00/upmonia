<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Covers signup verification, email-change verification, and resend — all in one table.
        // id = sha256(raw token); the raw token is only ever held client-side (in the email link).
        Schema::create('email_verification_tokens', function (Blueprint $table) {
            $table->string('id', 64)->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Address being proven — may differ from users.email during an email change.
            $table->string('email');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id', 'email_verification_tokens_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_verification_tokens');
    }
};
