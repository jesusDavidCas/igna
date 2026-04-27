<?php

use App\Enums\StageEventStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_stage_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_stage_id')->constrained()->restrictOnDelete();
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', array_column(StageEventStatus::cases(), 'value'))->default(StageEventStatus::PENDING->value);
            $table->boolean('is_client_visible')->default(true);
            $table->text('notes')->nullable();
            $table->timestamp('entered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['ticket_id', 'service_stage_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_stage_events');
    }
};
