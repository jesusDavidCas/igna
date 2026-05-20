<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        match (DB::getDriverName()) {
            'mysql', 'mariadb' => DB::statement('ALTER TABLE proposal_items MODIFY description TEXT NOT NULL'),
            'pgsql' => DB::statement('ALTER TABLE proposal_items ALTER COLUMN description TYPE TEXT'),
            default => null,
        };
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        match (DB::getDriverName()) {
            'mysql', 'mariadb' => DB::statement('ALTER TABLE proposal_items MODIFY description VARCHAR(255) NOT NULL'),
            'pgsql' => DB::statement('ALTER TABLE proposal_items ALTER COLUMN description TYPE VARCHAR(255)'),
            default => null,
        };
    }
};
