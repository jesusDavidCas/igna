<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_files', function (Blueprint $table): void {
            $table->foreignId('first_admin_downloaded_by_user_id')->nullable()->after('submitted_context_hash')->constrained('users')->nullOnDelete();
            $table->timestamp('first_admin_downloaded_at')->nullable()->after('first_admin_downloaded_by_user_id');
            $table->foreignId('rejected_by_user_id')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('rejected_by_user_id');
            $table->text('rejection_reason')->nullable()->after('rejected_at');
        });

        Schema::table('ticket_stage_events', function (Blueprint $table): void {
            $table->unsignedInteger('attempt_number')->default(1)->after('notes');
            $table->timestamp('superseded_at')->nullable()->after('completed_at');
            $table->foreignId('superseded_by_user_id')->nullable()->after('superseded_at')->constrained('users')->nullOnDelete();
            $table->text('superseded_reason')->nullable()->after('superseded_by_user_id');
        });

        Schema::create('ticket_stage_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_stage_event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_stage_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40);
            $table->string('status_before', 40)->nullable();
            $table->string('status_after', 40)->nullable();
            $table->unsignedInteger('attempt_number')->nullable();
            $table->timestamp('entered_at_snapshot')->nullable();
            $table->timestamp('completed_at_snapshot')->nullable();
            $table->text('notes_snapshot')->nullable();
            $table->foreignId('rollback_from_stage_id')->nullable()->constrained('service_stages')->nullOnDelete();
            $table->foreignId('rollback_to_stage_id')->nullable()->constrained('service_stages')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_stage_audits');

        Schema::table('ticket_stage_events', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('superseded_by_user_id');
            $table->dropColumn([
                'attempt_number',
                'superseded_at',
                'superseded_reason',
            ]);
        });

        Schema::table('ticket_files', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('first_admin_downloaded_by_user_id');
            $table->dropConstrainedForeignId('rejected_by_user_id');
            $table->dropColumn([
                'first_admin_downloaded_at',
                'rejected_at',
                'rejection_reason',
            ]);
        });
    }
};
