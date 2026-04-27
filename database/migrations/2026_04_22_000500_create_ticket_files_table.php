<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('original_name');
            $table->string('stored_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('storage_provider', 30)->default('local_stub');
            $table->string('storage_disk')->nullable();
            $table->string('storage_path')->nullable();
            $table->string('google_drive_file_id')->nullable();
            $table->string('google_drive_url')->nullable();
            $table->string('deliverable_type')->nullable();
            $table->boolean('is_client_visible')->default(false);
            $table->string('watermark_status', 30)->default('not_applicable');
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_files');
    }
};
