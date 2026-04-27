<?php

return [
    'service_types' => [
        'digital' => [
            'web_platform' => 'services.types.web_platform',
            'crm' => 'services.types.crm',
            'project_management' => 'services.types.project_management',
            'technical_structuring' => 'services.types.technical_structuring',
        ],
        'engineering' => [
            'aqueduct' => 'services.types.aqueduct',
            'sanitary_sewer' => 'services.types.sanitary_sewer',
            'stormwater_sewer' => 'services.types.stormwater_sewer',
            'fire_protection' => 'services.types.fire_protection',
            'hydrology' => 'services.types.hydrology',
            'ptap' => 'services.types.ptap',
            'ptar' => 'services.types.ptar',
        ],
    ],

    'service_scopes' => [
        'none' => 'services.scopes.none',
        'internal_networks' => 'services.scopes.internal_networks',
        'external_networks' => 'services.scopes.external_networks',
        'study' => 'services.scopes.study',
        'plant_project' => 'services.scopes.plant_project',
    ],
];
