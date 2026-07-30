<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var array<string, array<int, string>>
     */
    private array $englishDeliverables = [
        'WPD' => ['clear feature plan', 'delivery roadmap', 'launch notes'],
        'CRM' => ['customer workflow map', 'management modules', 'usage guide'],
        'TPM' => ['project plan', 'update rhythm', 'risk and decision log'],
        'TSD' => ['solution notes', 'clear project scope', 'implementation roadmap'],
        'ADI' => ['project descriptive report', 'network calculation report', 'hydraulic plans', 'Resolution 799 of 2021 references'],
        'ADE' => ['project descriptive report', 'network calculation report', 'hydraulic plans', 'Resolution 799 of 2021 references'],
        'SSI' => ['project descriptive report', 'network calculation report', 'sanitary plans', 'Resolution 799 of 2021 references'],
        'SSE' => ['project descriptive report', 'network calculation report', 'sanitary plans', 'Resolution 799 of 2021 references'],
        'SLI' => ['project descriptive report', 'drainage calculations', 'stormwater plans', 'Resolution 799 of 2021 references'],
        'SLE' => ['project descriptive report', 'drainage calculations', 'stormwater plans', 'Resolution 799 of 2021 references'],
        'FPN' => ['project descriptive report', 'network calculations', 'hydraulic plans', 'Resolution 799 of 2021 references', 'NSR10 reference'],
        'HYD' => ['project descriptive report', 'flow calculations', 'watershed plans', 'HEC-HMS and HEC-RAS models'],
        'PTP' => ['project descriptive report', 'hydraulic calculations', 'technical plans', 'Resolution 799 of 2021 references'],
        'PTR' => ['project descriptive report', 'network calculation reports', 'hydraulic plans', 'Resolution 799 of 2021 references'],
    ];

    /**
     * @var array<string, array<int, string>>
     */
    private array $spanishDeliverables = [
        'WPD' => ['plan claro de funciones', 'ruta de entrega', 'notas de lanzamiento'],
        'CRM' => ['mapa del flujo de clientes', 'módulos de gestión', 'guía de uso'],
        'TPM' => ['plan de proyecto', 'ritmo de actualización', 'registro de riesgos y decisiones'],
        'TSD' => ['notas de solución', 'alcance claro del proyecto', 'ruta de implementación'],
        'ADI' => ['memoria descriptiva del proyecto', 'informe de cálculo de redes', 'planos hidráulicos', 'referencias de la Resolución 799 de 2021'],
        'ADE' => ['memoria descriptiva del proyecto', 'informe de cálculo de redes', 'planos hidráulicos', 'referencias de la Resolución 799 de 2021'],
        'SSI' => ['memoria descriptiva del proyecto', 'informe de cálculo de redes', 'planos sanitarios', 'referencias de la Resolución 799 de 2021'],
        'SSE' => ['memoria descriptiva del proyecto', 'informe de cálculo de redes', 'planos sanitarios', 'referencias de la Resolución 799 de 2021'],
        'SLI' => ['memoria descriptiva del proyecto', 'cálculos de drenaje', 'planos de aguas lluvias', 'referencias de la Resolución 799 de 2021'],
        'SLE' => ['memoria descriptiva del proyecto', 'cálculos de drenaje', 'planos de aguas lluvias', 'referencias de la Resolución 799 de 2021'],
        'FPN' => ['memoria descriptiva del proyecto', 'cálculos de redes', 'planos hidráulicos', 'referencias de la Resolución 799 de 2021', 'referencia NSR10'],
        'HYD' => ['memoria descriptiva del proyecto', 'cálculos de caudal', 'planos de cuenca', 'modelos HEC-HMS y HEC-RAS'],
        'PTP' => ['memoria descriptiva del proyecto', 'cálculos hidráulicos', 'planos técnicos', 'referencias de la Resolución 799 de 2021'],
        'PTR' => ['memoria descriptiva del proyecto', 'informes de cálculo de redes', 'planos hidráulicos', 'referencias de la Resolución 799 de 2021'],
    ];

    public function up(): void
    {
        DB::table('services')
            ->select(['id', 'code'])
            ->whereIn('code', array_keys($this->spanishDeliverables))
            ->orderBy('id')
            ->each(function (object $service): void {
                $english = $this->englishDeliverables[$service->code] ?? [];
                $spanish = $this->spanishDeliverables[$service->code] ?? [];

                DB::table('service_deliverables')
                    ->where('service_id', $service->id)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get(['id', 'name', 'name_en', 'name_es', 'sort_order'])
                    ->values()
                    ->each(function (object $deliverable, int $index) use ($english, $spanish): void {
                        $source = trim((string) ($deliverable->name_en ?: $deliverable->name));
                        $englishValue = $english[$index] ?? $source;
                        $spanishValue = $spanish[$index] ?? null;

                        if ($spanishValue === null || $source === '') {
                            return;
                        }

                        DB::table('service_deliverables')
                            ->where('id', $deliverable->id)
                            ->update([
                                'name_en' => $englishValue,
                                'name_es' => $spanishValue,
                            ]);
                    });
            });
    }

    public function down(): void
    {
        DB::table('services')
            ->select(['id', 'code'])
            ->whereIn('code', array_keys($this->spanishDeliverables))
            ->orderBy('id')
            ->each(function (object $service): void {
                $spanish = $this->spanishDeliverables[$service->code] ?? [];

                DB::table('service_deliverables')
                    ->where('service_id', $service->id)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get(['id', 'name_es'])
                    ->values()
                    ->each(function (object $deliverable, int $index) use ($spanish): void {
                        if (($spanish[$index] ?? null) !== $deliverable->name_es) {
                            return;
                        }

                        DB::table('service_deliverables')
                            ->where('id', $deliverable->id)
                            ->update(['name_es' => null]);
                    });
            });
    }
};
