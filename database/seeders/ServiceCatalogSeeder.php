<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'name' => 'Web Platform Development',
                'code' => 'WPD',
                'business_line' => 'digital',
                'service_type' => 'web_platform',
                'service_scope' => 'none',
                'description' => 'Custom public and internal web platforms with lightweight operational workflows.',
                'deliverables_schema' => ['functional scope', 'delivery roadmap', 'platform release notes'],
                'stages' => [
                    ['name' => 'Request Intake', 'code' => 'INT', 'sort_order' => 1],
                    ['name' => 'Technical Structuring', 'code' => 'STR', 'sort_order' => 2],
                    ['name' => 'Execution', 'code' => 'EXE', 'sort_order' => 3],
                    ['name' => 'Delivery', 'code' => 'DEL', 'sort_order' => 4],
                ],
            ],
            [
                'name' => 'Simple Customer Tracking Systems',
                'code' => 'CRM',
                'business_line' => 'digital',
                'service_type' => 'crm',
                'service_scope' => 'none',
                'description' => 'Simple systems for tracking customers, requests, conversations, and next steps.',
                'deliverables_schema' => ['customer workflow map', 'management modules', 'user guidance'],
                'stages' => [
                    ['name' => 'Request Intake', 'code' => 'INT', 'sort_order' => 1],
                    ['name' => 'Process Definition', 'code' => 'PRC', 'sort_order' => 2],
                    ['name' => 'Configuration', 'code' => 'CFG', 'sort_order' => 3],
                    ['name' => 'Launch', 'code' => 'LCH', 'sort_order' => 4],
                ],
            ],
            [
                'name' => 'Technology Project Management',
                'code' => 'TPM',
                'business_line' => 'digital',
                'service_type' => 'project_management',
                'service_scope' => 'none',
                'description' => 'Operational follow-up and technical coordination for digital initiatives.',
                'deliverables_schema' => ['project plan', 'status reporting cadence', 'risk log'],
                'stages' => [
                    ['name' => 'Request Intake', 'code' => 'INT', 'sort_order' => 1],
                    ['name' => 'Alignment', 'code' => 'ALN', 'sort_order' => 2],
                    ['name' => 'Execution Oversight', 'code' => 'OVR', 'sort_order' => 3],
                    ['name' => 'Closure', 'code' => 'CLS', 'sort_order' => 4],
                ],
            ],
            [
                'name' => 'Technical Structuring of Digital Projects',
                'code' => 'TSD',
                'business_line' => 'digital',
                'service_type' => 'technical_structuring',
                'service_scope' => 'none',
                'description' => 'Architecture, scoping, and technical decision support for digital project planning.',
                'deliverables_schema' => ['architecture notes', 'technical specification', 'implementation roadmap'],
                'stages' => [
                    ['name' => 'Request Intake', 'code' => 'INT', 'sort_order' => 1],
                    ['name' => 'Diagnostic', 'code' => 'DIA', 'sort_order' => 2],
                    ['name' => 'Structuring', 'code' => 'STR', 'sort_order' => 3],
                    ['name' => 'Handover', 'code' => 'DEL', 'sort_order' => 4],
                ],
            ],
            [
                'name' => 'Aqueduct Design - Internal Networks',
                'code' => 'ADI',
                'business_line' => 'engineering',
                'service_type' => 'aqueduct',
                'service_scope' => 'internal_networks',
                'description' => 'Hydraulic design for internal aqueduct distribution networks.',
                'deliverables_schema' => ['project descriptive report', 'network calculation report', 'hydraulic plans', 'Resolution 799 of 2021 references'],
                'stages' => $this->engineeringStages(),
            ],
            [
                'name' => 'Aqueduct Design - External Networks',
                'code' => 'ADE',
                'business_line' => 'engineering',
                'service_type' => 'aqueduct',
                'service_scope' => 'external_networks',
                'description' => 'Hydraulic design for external aqueduct distribution networks.',
                'deliverables_schema' => ['project descriptive report', 'network calculation report', 'hydraulic plans', 'Resolution 799 of 2021 references'],
                'stages' => $this->engineeringStages(),
            ],
            [
                'name' => 'Sanitary Sewer Design - Internal Networks',
                'code' => 'SSI',
                'business_line' => 'engineering',
                'service_type' => 'sanitary_sewer',
                'service_scope' => 'internal_networks',
                'description' => 'Sanitary sewer design for internal private or institutional projects.',
                'deliverables_schema' => ['project descriptive report', 'network calculation report', 'sanitary plans', 'Resolution 799 of 2021 references'],
                'stages' => $this->engineeringStages(),
            ],
            [
                'name' => 'Sanitary Sewer Design - External Networks',
                'code' => 'SSE',
                'business_line' => 'engineering',
                'service_type' => 'sanitary_sewer',
                'service_scope' => 'external_networks',
                'description' => 'Sanitary sewer design for external collection systems.',
                'deliverables_schema' => ['project descriptive report', 'network calculation report', 'sanitary plans', 'Resolution 799 of 2021 references'],
                'stages' => $this->engineeringStages(),
            ],
            [
                'name' => 'Stormwater Sewer Design - Internal Networks',
                'code' => 'SLI',
                'business_line' => 'engineering',
                'service_type' => 'stormwater_sewer',
                'service_scope' => 'internal_networks',
                'description' => 'Stormwater sewer design for internal networks.',
                'deliverables_schema' => ['project descriptive report', 'drainage calculations', 'stormwater plans', 'Resolution 799 of 2021 references'],
                'stages' => $this->engineeringStages(),
            ],
            [
                'name' => 'Stormwater Sewer Design - External Networks',
                'code' => 'SLE',
                'business_line' => 'engineering',
                'service_type' => 'stormwater_sewer',
                'service_scope' => 'external_networks',
                'description' => 'Stormwater sewer design for external networks.',
                'deliverables_schema' => ['project descriptive report', 'drainage calculations', 'stormwater plans', 'Resolution 799 of 2021 references'],
                'stages' => $this->engineeringStages(),
            ],
            [
                'name' => 'Fire Protection Network Design',
                'code' => 'FPN',
                'business_line' => 'engineering',
                'service_type' => 'fire_protection',
                'service_scope' => 'study',
                'description' => 'Hydraulic design of fire protection networks.',
                'deliverables_schema' => ['project descriptive report', 'network calculations', 'hydraulic plans', 'Resolution 799 of 2021 references', 'NSR10 reference'],
                'stages' => $this->engineeringStages(),
            ],
            [
                'name' => 'Hydrology Studies',
                'code' => 'HYD',
                'business_line' => 'engineering',
                'service_type' => 'hydrology',
                'service_scope' => 'study',
                'description' => 'Hydrology studies, flow calculations, and basin simulation support.',
                'deliverables_schema' => ['project descriptive report', 'flow calculations', 'watershed plans', 'HEC-HMS and HEC-RAS models'],
                'stages' => $this->engineeringStages(),
            ],
            [
                'name' => 'Drinking Water Treatment Plant Projects',
                'code' => 'PTP',
                'business_line' => 'engineering',
                'service_type' => 'ptap',
                'service_scope' => 'plant_project',
                'description' => 'Design and technical structuring for drinking water treatment plant projects.',
                'deliverables_schema' => ['project descriptive report', 'hydraulic calculations', 'technical plans', 'Resolution 799 of 2021 references'],
                'stages' => $this->engineeringStages(),
            ],
            [
                'name' => 'Wastewater Treatment Plant Projects',
                'code' => 'PTR',
                'business_line' => 'engineering',
                'service_type' => 'ptar',
                'service_scope' => 'plant_project',
                'description' => 'Design and technical structuring for wastewater treatment plant projects.',
                'deliverables_schema' => ['project descriptive report', 'network calculation reports', 'hydraulic plans', 'Resolution 799 of 2021 references'],
                'stages' => $this->engineeringStages(),
            ],
        ];

        foreach ($services as $index => $payload) {
            $service = Service::query()->updateOrCreate(
                ['code' => $payload['code']],
                [
                    'name' => $payload['name'],
                    'slug' => Str::slug($payload['name']),
                    'business_line' => $payload['business_line'],
                    'service_type' => $payload['service_type'],
                    'service_scope' => $payload['service_scope'],
                    'description' => $payload['description'],
                    'deliverables_schema' => $payload['deliverables_schema'],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            );

            foreach ($payload['stages'] as $stage) {
                $service->stages()->updateOrCreate(
                    ['code' => $stage['code']],
                    [
                        'name' => $stage['name'],
                        'description' => $stage['description'] ?? null,
                        'sort_order' => $stage['sort_order'],
                        'is_active' => true,
                        'is_client_visible' => $stage['is_client_visible'] ?? true,
                    ],
                );
            }
        }
    }

    private function engineeringStages(): array
    {
        return [
            ['name' => 'Request Intake', 'code' => 'INT', 'sort_order' => 1],
            ['name' => 'Technical Review', 'code' => 'REV', 'sort_order' => 2],
            ['name' => 'Design Development', 'code' => 'DSN', 'sort_order' => 3],
            ['name' => 'Delivery', 'code' => 'DEL', 'sort_order' => 4],
        ];
    }
}
