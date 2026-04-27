<?php

return [
    'types' => [
        'web_platform' => 'Web tools for teams and clients',
        'crm' => 'Simple customer tracking systems',
        'project_management' => 'Digital project coordination',
        'technical_structuring' => 'Planning a digital solution before building',
        'aqueduct' => 'Drinking water network design',
        'sanitary_sewer' => 'Wastewater collection design',
        'stormwater_sewer' => 'Stormwater drainage design',
        'fire_protection' => 'Fire protection water network design',
        'hydrology' => 'Rainfall and watershed studies',
        'ptap' => 'Drinking water treatment plant project',
        'ptar' => 'Wastewater treatment plant project',
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
        'ADI' => ['name' => 'Drinking water distribution inside your project', 'description' => 'We design the internal water network so buildings, facilities, or developments can operate with a clear and technically supported supply layout.'],
        'ADE' => ['name' => 'External drinking water connections and networks', 'description' => 'We support the design of external water supply networks and connection layouts so your project can connect responsibly to the broader system.'],
        'SSI' => ['name' => 'Wastewater collection inside your project', 'description' => 'We design internal wastewater systems so your project can collect and move sanitary flows safely, clearly, and in line with technical requirements.'],
        'SSE' => ['name' => 'External wastewater collection systems', 'description' => 'We help structure external wastewater networks and collection routes for projects that need dependable sanitary infrastructure.'],
        'SLI' => ['name' => 'Stormwater drainage inside your project', 'description' => 'We design internal drainage systems to manage rainwater and reduce operational, construction, and flooding risks.'],
        'SLE' => ['name' => 'External stormwater drainage networks', 'description' => 'We support stormwater network design for roads, developments, and surrounding areas that need organized rainwater management.'],
        'FPN' => ['name' => 'Water network design for fire protection', 'description' => 'We help define the water network needed to support fire protection requirements with technical calculations and clear plans.'],
        'HYD' => ['name' => 'Rainfall, flow, and watershed studies', 'description' => 'We analyze rainfall, flows, basins, and runoff behavior so your project can make better design and risk decisions.'],
        'PTP' => ['name' => 'Drinking water treatment plant planning and design', 'description' => 'We support the technical design and documentation needed for projects that treat water for safe use and supply.'],
        'PTR' => ['name' => 'Wastewater treatment plant planning and design', 'description' => 'We help structure the technical design and documentation for projects that treat wastewater before discharge or reuse.'],
    ],
];
