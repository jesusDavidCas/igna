<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_deliverables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_client_visible_by_default')->default(true);
            $table->timestamps();
        });

        Schema::create('ticket_deliverables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_deliverable_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 40)->default('pending');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('ticket_files', function (Blueprint $table) {
            $table->foreignId('ticket_deliverable_id')->nullable()->after('uploaded_by_user_id')->constrained()->nullOnDelete();
            $table->string('visibility', 30)->default('internal')->after('deliverable_type');
            $table->string('delivery_type', 30)->default('internal')->after('visibility');
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('header_image_path')->nullable()->after('summary');
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('signature_path')->nullable()->after('remember_token');
        });

        Schema::table('proposals', function (Blueprint $table) {
            $table->foreignId('signer_user_id')->nullable()->after('created_by_user_id')->constrained('users')->nullOnDelete();
            $table->string('source_excel_path')->nullable()->after('payment_schedule');
            $table->string('source_excel_original_name')->nullable()->after('source_excel_path');
        });

        DB::table('services')
            ->select(['id', 'deliverables_schema'])
            ->orderBy('id')
            ->get()
            ->each(function ($service): void {
                $deliverables = json_decode((string) $service->deliverables_schema, true);

                if (! is_array($deliverables)) {
                    return;
                }

                foreach (array_values(array_filter($deliverables)) as $index => $name) {
                    DB::table('service_deliverables')->insert([
                        'service_id' => $service->id,
                        'name' => $name,
                        'description' => null,
                        'sort_order' => $index + 1,
                        'is_active' => true,
                        'is_client_visible_by_default' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('signer_user_id');
            $table->dropColumn(['source_excel_path', 'source_excel_original_name']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('signature_path');
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn('header_image_path');
            $table->dropSoftDeletes();
        });

        Schema::table('ticket_files', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ticket_deliverable_id');
            $table->dropColumn(['visibility', 'delivery_type']);
        });

        Schema::dropIfExists('ticket_deliverables');
        Schema::dropIfExists('service_deliverables');
    }
};
