<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_credentials', function (Blueprint $table): void {
            $table->string('protected_document_path')->nullable()->after('document_path');
            $table->string('original_checksum', 64)->nullable()->after('size_bytes');
            $table->string('protected_checksum', 64)->nullable()->after('original_checksum');
            $table->timestamp('protected_generated_at')->nullable()->after('protected_checksum');
            $table->unsignedInteger('protection_version')->default(1)->after('protected_generated_at');
            $table->string('protection_status', 30)->default('pending')->after('protection_version');
            $table->string('protection_error')->nullable()->after('protection_status');
        });
    }

    public function down(): void
    {
        Schema::table('team_credentials', function (Blueprint $table): void {
            $table->dropColumn([
                'protected_document_path',
                'original_checksum',
                'protected_checksum',
                'protected_generated_at',
                'protection_version',
                'protection_status',
                'protection_error',
            ]);
        });
    }
};
