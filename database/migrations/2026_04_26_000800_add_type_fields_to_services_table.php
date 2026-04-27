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
        Schema::table('services', function (Blueprint $table) {
            $table->string('service_type', 60)->nullable()->after('business_line');
            $table->string('service_scope', 60)->nullable()->after('service_type');
        });

        DB::table('services')->orderBy('id')->each(function (object $service): void {
            DB::table('services')
                ->where('id', $service->id)
                ->update($this->classify($service->name, $service->business_line));
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['service_type', 'service_scope']);
        });
    }

    private function classify(string $name, string $businessLine): array
    {
        $normalized = Str::lower($name);

        if ($businessLine === 'digital') {
            return [
                'service_type' => match (true) {
                    str_contains($normalized, 'crm') => 'crm',
                    str_contains($normalized, 'management') => 'project_management',
                    str_contains($normalized, 'structuring') => 'technical_structuring',
                    default => 'web_platform',
                },
                'service_scope' => 'none',
            ];
        }

        return [
            'service_type' => match (true) {
                str_contains($normalized, 'aqueduct') => 'aqueduct',
                str_contains($normalized, 'sanitary') => 'sanitary_sewer',
                str_contains($normalized, 'stormwater') => 'stormwater_sewer',
                str_contains($normalized, 'fire') => 'fire_protection',
                str_contains($normalized, 'hydrology') => 'hydrology',
                str_contains($normalized, 'ptap') => 'ptap',
                str_contains($normalized, 'ptar') => 'ptar',
                default => 'aqueduct',
            },
            'service_scope' => match (true) {
                str_contains($normalized, 'internal') => 'internal_networks',
                str_contains($normalized, 'external') => 'external_networks',
                str_contains($normalized, 'hydrology') => 'study',
                str_contains($normalized, 'ptap'), str_contains($normalized, 'ptar') => 'plant_project',
                default => 'study',
            },
        ];
    }
};
