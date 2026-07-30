<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table): void {
            $table->string('title_en')->nullable()->after('title');
            $table->string('title_es')->nullable()->after('title_en');
        });

        DB::table('proposals')
            ->orderBy('id')
            ->each(function (object $proposal): void {
                DB::table('proposals')
                    ->where('id', $proposal->id)
                    ->update(['title_en' => $proposal->title]);
            });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table): void {
            $table->dropColumn(['title_en', 'title_es']);
        });
    }
};
