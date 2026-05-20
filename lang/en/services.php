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
        'ADI' => ['name' => 'Bring drinking water to every point in your project', 'description' => 'We organize how water should move inside the building, property, or development so the supply is clear, safe, and easier to review.'],
        'ADE' => ['name' => 'Connect your project to the water system', 'description' => 'We help solve the outside water connection and prepare a technical path that makes sense for the project surroundings.'],
        'SSI' => ['name' => 'Move wastewater out safely', 'description' => 'We define how wastewater should be collected and carried inside the project to reduce risk and leave a well-documented solution.'],
        'SSE' => ['name' => 'Organize the external wastewater route', 'description' => 'We support the outside route that carries wastewater to the right point, with clarity for review and execution.'],
        'SLI' => ['name' => 'Avoid rainwater problems inside the project', 'description' => 'We help manage rainwater inside the property to reduce pooling, damage, delays, or flooding risk.'],
        'SLE' => ['name' => 'Manage rainwater around the project', 'description' => 'We organize the outside rainwater path so roads, shared areas, or developments have a cleaner solution.'],
        'FPN' => ['name' => 'Prepare the water network for fire protection', 'description' => 'We help define a fire protection water solution with calculations, criteria, and plans that make review easier.'],
        'HYD' => ['name' => 'Understand how water behaves on the land', 'description' => 'We study rainfall, flows, and watersheds so you can make better design decisions and reduce risk early.'],
        'PTP' => ['name' => 'Prepare a project to treat drinking water', 'description' => 'We support the technical organization of a treatment plant or system so water can be used more safely.'],
        'PTR' => ['name' => 'Prepare a project to treat wastewater', 'description' => 'We help structure the system that treats wastewater before discharge or reuse, with clear deliverables.'],
    ],
];
