<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->string('public_token', 64)->nullable()->unique()->after('proposal_number');
            $table->string('prospect_name')->nullable()->after('client_user_id');
            $table->string('prospect_email')->nullable()->after('prospect_name');
            $table->string('prospect_phone', 80)->nullable()->after('prospect_email');
        });

        DB::table('proposals')
            ->whereNull('public_token')
            ->orderBy('id')
            ->lazyById()
            ->each(function (object $proposal): void {
                do {
                    $token = Str::random(40);
                } while (DB::table('proposals')->where('public_token', $token)->exists());

                DB::table('proposals')
                    ->where('id', $proposal->id)
                    ->update(['public_token' => $token]);
            });

        // Keep the column nullable at schema level for maximum database compatibility;
        // the model guarantees a token for newly-created proposals.
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropUnique(['public_token']);
            $table->dropColumn(['public_token', 'prospect_name', 'prospect_email', 'prospect_phone']);
        });
    }
};
