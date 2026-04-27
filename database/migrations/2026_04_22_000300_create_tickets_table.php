<?php

use App\Enums\TicketStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_code')->unique();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('current_service_stage_id')->nullable()->constrained('service_stages')->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->index();
            $table->string('phone')->nullable();
            $table->string('project_name');
            $table->string('project_location')->nullable();
            $table->string('preferred_language', 5)->default('es');
            $table->longText('project_description');
            $table->date('target_date')->nullable();
            $table->enum('status', array_column(TicketStatus::cases(), 'value'))->default(TicketStatus::NEW->value);
            $table->string('google_drive_folder_id')->nullable();
            $table->string('google_drive_folder_url')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
