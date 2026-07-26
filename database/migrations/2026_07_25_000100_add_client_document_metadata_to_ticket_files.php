<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_files', function (Blueprint $table): void {
            $table->string('upload_source', 40)->default('admin')->after('delivery_type');
            $table->string('review_status', 40)->default('reviewed')->after('upload_source');
            $table->string('submitted_context_hash', 128)->nullable()->after('review_status');
            $table->foreignId('reviewed_by_user_id')->nullable()->after('submitted_context_hash')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_files', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reviewed_by_user_id');
            $table->dropColumn([
                'upload_source',
                'review_status',
                'submitted_context_hash',
                'reviewed_at',
            ]);
        });
    }
};
