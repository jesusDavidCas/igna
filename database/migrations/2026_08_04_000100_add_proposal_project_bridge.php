<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->foreignId('proposal_id')->nullable()->after('id');
            $table->unique('proposal_id', 'tickets_prop_id_uq');
            $table->foreign('proposal_id', 'tickets_prop_id_fk')
                ->references('id')
                ->on('proposals')
                ->nullOnDelete();
        });

        Schema::table('proposals', function (Blueprint $table): void {
            $table->string('project_location')->nullable()->after('prospect_phone');
            $table->date('requested_deadline')->nullable()->after('project_location');
            $table->timestamp('converted_to_project_at')->nullable()->after('validity_days');
            $table->foreignId('converted_by_user_id')->nullable()->after('converted_to_project_at');
            $table->foreign('converted_by_user_id', 'props_conv_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table): void {
            $table->dropForeign(['converted_by_user_id']);
            $table->dropColumn([
                'project_location',
                'requested_deadline',
                'converted_to_project_at',
                'converted_by_user_id',
            ]);
        });

        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropForeign(['proposal_id']);
            $table->dropUnique('tickets_prop_id_uq');
            $table->dropColumn('proposal_id');
        });
    }
};
