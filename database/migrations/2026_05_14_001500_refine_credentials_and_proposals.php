<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_credentials', function (Blueprint $table) {
            $table->unsignedInteger('preview_page_count')->default(1)->after('size_bytes');
        });

        Schema::table('proposals', function (Blueprint $table) {
            $table->unsignedSmallInteger('timeline_months')->default(0)->after('scope');
            $table->unsignedSmallInteger('timeline_weeks')->default(0)->after('timeline_months');
            $table->json('payment_schedule')->nullable()->after('payment_plan');
        });

        Schema::table('proposal_items', function (Blueprint $table) {
            $table->string('category')->nullable()->after('proposal_id');
            $table->string('item_code', 40)->nullable()->after('category');
            $table->string('unit', 40)->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('proposal_items', function (Blueprint $table) {
            $table->dropColumn(['category', 'item_code', 'unit']);
        });

        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn(['timeline_months', 'timeline_weeks', 'payment_schedule']);
        });

        Schema::table('team_credentials', function (Blueprint $table) {
            $table->dropColumn('preview_page_count');
        });
    }
};
