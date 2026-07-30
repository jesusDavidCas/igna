<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->string('service_selection', 30)->default('catalog')->after('service_id');
            $table->string('service_public_category', 60)->nullable()->after('service_selection');
        });

        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropForeign(['service_id']);
            $table->foreignId('service_id')->nullable()->change();
            $table->foreign('service_id')->references('id')->on('services')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('tickets')->whereNull('service_id')->exists()) {
            throw new RuntimeException('Cannot roll back other-service request support while tickets without service_id exist.');
        }

        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropForeign(['service_id']);
            $table->foreignId('service_id')->nullable(false)->change();
            $table->foreign('service_id')->references('id')->on('services')->restrictOnDelete();
        });

        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropColumn(['service_selection', 'service_public_category']);
        });
    }
};
