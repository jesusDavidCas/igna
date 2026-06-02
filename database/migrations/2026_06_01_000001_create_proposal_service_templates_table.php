<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_service_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->unsignedSmallInteger('service_number')->index();
            $table->string('name_es');
            $table->string('name_en');
            $table->string('landing_title_es');
            $table->string('landing_title_en');
            $table->text('landing_description_es')->nullable();
            $table->text('landing_description_en')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('proposal_service_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_service_template_id')->constrained()->cascadeOnDelete();
            $table->string('item_code', 40)->nullable();
            $table->text('description_es');
            $table->text('description_en')->nullable();
            $table->string('unit', 40)->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->decimal('unit_value', 14, 2)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_service_template_items');
        Schema::dropIfExists('proposal_service_templates');
    }
};
