<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Basis
            $table->string('name', 120);
            $table->string('email', 160);
            $table->string('phone', 40)->nullable();
            $table->string('subject', 160);
            $table->longText('message');

            // HIER STAAT HIJ: Dit slaat de checkbox op (true/false)
            $table->boolean('consent')->default(false);

            // Status & afhandeling
            $table->string('status', 20)->default('new');

            // Let op: verwijzing naar 'admins' tabel voor de beheerder
            $table->foreignId('handled_by')->nullable()->constrained('admins')->nullOnDelete();

            $table->timestamp('read_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamp('archived_at')->nullable();

            // Anti-spam
            $table->boolean('hp_filled')->default(false);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->boolean('is_spam')->default(false);
            $table->string('spam_reason', 255)->nullable();
            $table->char('dedupe_hash', 64)->nullable();

            // Meta
            $table->ipAddress('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('referer', 255)->nullable();
            $table->json('utm')->nullable();
            $table->string('locale', 8)->default('nl');
            $table->string('source', 24)->default('web');

            // Relationeel met gewone gebruiker (indien ingelogd)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->index('user_id');

            $table->timestamps();
            $table->softDeletes();

            // Indexen
            $table->index(['status', 'is_spam']);
            $table->index(['email']);
            $table->index(['created_at']);
            $table->unique(['dedupe_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
