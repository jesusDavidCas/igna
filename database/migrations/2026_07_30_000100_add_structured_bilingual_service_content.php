<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->string('name_en')->nullable()->after('name');
            $table->string('name_es')->nullable()->after('name_en');
            $table->text('description_en')->nullable()->after('description');
            $table->text('description_es')->nullable()->after('description_en');
            $table->json('legacy_deliverables_schema')->nullable()->after('deliverables_schema');
            $table->json('deliverables_normalization_notes')->nullable()->after('legacy_deliverables_schema');
        });

        Schema::table('service_deliverables', function (Blueprint $table): void {
            $table->string('name_en')->nullable()->after('name');
            $table->string('name_es')->nullable()->after('name_en');
            $table->text('description_en')->nullable()->after('description');
            $table->text('description_es')->nullable()->after('description_en');
        });

        Schema::table('service_stages', function (Blueprint $table): void {
            $table->string('name_en')->nullable()->after('name');
            $table->string('name_es')->nullable()->after('name_en');
            $table->text('description_en')->nullable()->after('description');
            $table->text('description_es')->nullable()->after('description_en');
        });

        $this->backfillStructuredContent();
    }

    public function down(): void
    {
        Schema::table('service_stages', function (Blueprint $table): void {
            $table->dropColumn(['name_en', 'name_es', 'description_en', 'description_es']);
        });

        Schema::table('service_deliverables', function (Blueprint $table): void {
            $table->dropColumn(['name_en', 'name_es', 'description_en', 'description_es']);
        });

        Schema::table('services', function (Blueprint $table): void {
            $table->dropColumn([
                'name_en',
                'name_es',
                'description_en',
                'description_es',
                'legacy_deliverables_schema',
                'deliverables_normalization_notes',
            ]);
        });
    }

    private function backfillStructuredContent(): void
    {
        DB::table('services')
            ->orderBy('id')
            ->each(function (object $service): void {
                $legacy = $this->decodeJson($service->deliverables_schema);
                $normalized = $this->normalizeLegacyDeliverables($legacy);
                $ambiguous = $legacy !== [] && $normalized === $legacy ? [] : [
                    'legacy_deliverables_schema_preserved' => true,
                    'normalization' => 'newline and pipe delimiters only',
                ];

                DB::table('services')
                    ->where('id', $service->id)
                    ->update([
                        'name_en' => $service->name,
                        'description_en' => $service->description,
                        'legacy_deliverables_schema' => $legacy === [] ? null : json_encode($legacy),
                        'deliverables_schema' => $normalized === [] ? $service->deliverables_schema : json_encode($normalized),
                        'deliverables_normalization_notes' => $ambiguous === [] ? null : json_encode($ambiguous),
                    ]);
            });

        DB::table('service_deliverables')
            ->orderBy('id')
            ->each(function (object $deliverable): void {
                DB::table('service_deliverables')
                    ->where('id', $deliverable->id)
                    ->update([
                        'name_en' => $deliverable->name,
                        'description_en' => $deliverable->description,
                    ]);
            });

        DB::table('service_stages')
            ->orderBy('id')
            ->each(function (object $stage): void {
                DB::table('service_stages')
                    ->where('id', $stage->id)
                    ->update([
                        'name_en' => $stage->name,
                        'description_en' => $stage->description,
                    ]);
            });
    }

    /**
     * @return array<int, string>
     */
    private function decodeJson(?string $value): array
    {
        $decoded = json_decode((string) $value, true);

        if (! is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->map(fn (mixed $item): string => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $deliverables
     * @return array<int, string>
     */
    private function normalizeLegacyDeliverables(array $deliverables): array
    {
        return collect($deliverables)
            ->flatMap(function (string $item): array {
                $lines = preg_split('/\r\n|\r|\n/', $item) ?: [];

                return collect($lines)
                    ->flatMap(fn (string $line): array => str_contains($line, '|') ? explode('|', $line) : [$line])
                    ->all();
            })
            ->map(fn (string $item): string => trim($item))
            ->filter()
            ->values()
            ->all();
    }
};
