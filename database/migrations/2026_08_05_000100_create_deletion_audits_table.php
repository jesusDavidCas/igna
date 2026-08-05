<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deletion_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_user_id')->nullable();
            $table->string('actor_email_snapshot')->nullable();
            $table->string('entity_type', 80);
            $table->string('entity_public_identifier', 120);
            $table->string('entity_label')->nullable();
            $table->json('dependency_summary')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('actor_user_id', 'del_aud_actor_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->index(['entity_type', 'entity_public_identifier'], 'del_aud_entity_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deletion_audits');
    }
};
