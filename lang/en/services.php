<?php

return [
    'types' => [
        'web_platform' => 'One clear place to manage work and clients',
        'crm' => 'Simple follow-up for clients and requests',
        'project_management' => 'Support so your project does not get lost',
        'technical_structuring' => 'Organizing a digital idea before building it',
        'aqueduct' => 'Drinking water for your project',
        'sanitary_sewer' => 'Safe handling of wastewater',
        'stormwater_sewer' => 'Managing rainwater before it becomes a problem',
        'fire_protection' => 'Water readiness for fire protection',
        'hydrology' => 'Understanding rainfall, flows, and risk',
        'ptap' => 'Treated water for safe use',
        'ptar' => 'Wastewater treatment',
    ],
    'scopes' => [
        'none' => 'General support',
        'internal_networks' => 'Inside the property or facility',
        'external_networks' => 'Outside connection or public-facing network',
        'study' => 'Study and technical guidance',
        'plant_project' => 'Treatment plant project',
    ],
    'catalog' => [
        'WPD' => [
            'name' => 'A clear web system for your team or clients',
            'description' => 'When your work is spread across messages, spreadsheets, and manual follow-ups, we help you build a focused web platform that brings the important information into one place.',
            'deliverables' => ['clear feature plan', 'delivery roadmap', 'launch notes'],
        ],
        'CRM' => [
            'name' => 'A simple system to track and manage your customers',
            'description' => 'We help you follow leads, requests, conversations, and next steps without forcing your team into a heavy tool they will not use.',
            'deliverables' => ['customer workflow map', 'management modules', 'usage guide'],
        ],
        'TPM' => [
            'name' => 'Steady coordination for your digital project',
            'description' => 'If your project has moving parts, vendors, decisions, and deadlines, we help organize the work so progress is visible and decisions do not get lost.',
            'deliverables' => ['project plan', 'update rhythm', 'risk and decision log'],
        ],
        'TSD' => [
            'name' => 'A practical plan before you invest in software',
            'description' => 'Before writing code, we help define what should be built, what can wait, and how the system should work so you avoid expensive confusion later.',
            'deliverables' => ['solution notes', 'clear project scope', 'implementation roadmap'],
        ],
        'ADI' => ['name' => 'Bring drinking water to every point in your project', 'description' => 'We organize how water should be distributed inside the building, property, or development so the supply is efficient, safe, and easier to maintain.', 'deliverables' => ['project descriptive report', 'network calculation report', 'hydraulic plans', 'Resolution 799 of 2021 references']],
        'ADE' => ['name' => 'Connect your project to the public water system', 'description' => 'We help solve the exterior drinking water connection and prepare a technical proposal that fits the project surroundings.', 'deliverables' => ['project descriptive report', 'network calculation report', 'hydraulic plans', 'Resolution 799 of 2021 references']],
        'SSI' => ['name' => 'Move wastewater safely through the project', 'description' => 'We define how wastewater should be carried through the project in a sealed, safe way to reduce risk and leave a clear technical solution.', 'deliverables' => ['project descriptive report', 'network calculation report', 'sanitary plans', 'Resolution 799 of 2021 references']],
        'SSE' => ['name' => 'Set the exterior wastewater discharge route', 'description' => 'We support the exterior route for carrying wastewater to the correct point while meeting mandatory sanitary requirements.', 'deliverables' => ['project descriptive report', 'network calculation report', 'sanitary plans', 'Resolution 799 of 2021 references']],
        'SLI' => ['name' => 'Avoid rainwater problems inside the project', 'description' => 'We design the stormwater network inside the project to reduce pooling, damage, and flooding risk.', 'deliverables' => ['project descriptive report', 'drainage calculations', 'stormwater plans', 'Resolution 799 of 2021 references']],
        'SLE' => ['name' => 'Manage rainwater around the project', 'description' => 'We organize the outside rainwater path so roads, shared areas, or developments have a cleaner solution.', 'deliverables' => ['project descriptive report', 'drainage calculations', 'stormwater plans', 'Resolution 799 of 2021 references']],
        'FPN' => ['name' => 'Design the water network for fire protection', 'description' => 'We help define a fire protection water solution with calculations, criteria, and plans that make construction easier.', 'deliverables' => ['project descriptive report', 'network calculations', 'hydraulic plans', 'Resolution 799 of 2021 references', 'NSR10 reference']],
        'HYD' => ['name' => 'Understand rainfall, flows, and risk', 'description' => 'We model watersheds so you can make better-informed decisions and reduce undersizing risks from the design stage.', 'deliverables' => ['project descriptive report', 'flow calculations', 'watershed plans', 'HEC-HMS and HEC-RAS models']],
        'PTP' => ['name' => 'Prepare a plant or system to treat drinking water', 'description' => 'We support the technical organization of a treatment plant or system so water can be consumed more safely.', 'deliverables' => ['project descriptive report', 'hydraulic calculations', 'technical plans', 'Resolution 799 of 2021 references']],
        'PTR' => ['name' => 'Structure wastewater treatment', 'description' => 'We help structure the system that treats wastewater before discharge or reuse, with a high-quality technical solution.', 'deliverables' => ['project descriptive report', 'network calculation reports', 'hydraulic plans', 'Resolution 799 of 2021 references']],
    ],
];
