<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_post_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('blog_post_id')->constrained()->restrictOnDelete();
            $table->string('locale', 5);
            $table->string('title', 180)->nullable();
            $table->text('summary')->nullable();
            $table->longText('body_html')->nullable();
            $table->string('seo_title', 180)->nullable();
            $table->string('seo_description', 160)->nullable();
            $table->string('status', 20)->default('draft');
            $table->char('source_fingerprint', 64)->nullable();
            $table->char('approved_source_fingerprint', 64)->nullable();
            $table->timestamp('stale_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['blog_post_id', 'locale']);
            $table->index(['locale', 'status', 'approved_at']);
        });

        Schema::create('team_member_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_member_id')->constrained()->restrictOnDelete();
            $table->string('locale', 5);
            $table->string('role', 180)->nullable();
            $table->text('short_description')->nullable();
            $table->json('bio')->nullable();
            $table->json('expertise')->nullable();
            $table->string('seo_title', 180)->nullable();
            $table->string('seo_description', 160)->nullable();
            $table->string('status', 20)->default('draft');
            $table->char('source_fingerprint', 64)->nullable();
            $table->char('approved_source_fingerprint', 64)->nullable();
            $table->timestamp('stale_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['team_member_id', 'locale']);
            $table->index(['locale', 'status', 'approved_at']);
        });
    }

    public function down(): void
    {
        foreach (['blog_post_translations', 'team_member_translations'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException("Refusing to drop populated {$table}; archive translations before rollback.");
            }
        }

        Schema::dropIfExists('team_member_translations');
        Schema::dropIfExists('blog_post_translations');
    }
};
