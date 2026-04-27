<?php

return [
    'types' => [
        'web_platform' => 'Herramientas web para equipos y clientes',
        'crm' => 'Sistemas simples para gestionar clientes',
        'project_management' => 'Coordinación de proyectos digitales',
        'technical_structuring' => 'Planeación de una solución digital antes de construir',
        'aqueduct' => 'Diseño de redes de agua potable',
        'sanitary_sewer' => 'Diseño de recolección de aguas residuales',
        'stormwater_sewer' => 'Diseño de drenaje de aguas lluvias',
        'fire_protection' => 'Diseño de redes hidráulicas contra incendios',
        'hydrology' => 'Estudios de lluvia y cuencas',
        'ptap' => 'Proyecto de planta de tratamiento de agua potable',
        'ptar' => 'Proyecto de planta de tratamiento de aguas residuales',
    ],
    'scopes' => [
        'none' => 'Apoyo general',
        'internal_networks' => 'Dentro del predio o instalación',
        'external_networks' => 'Conexión exterior o red hacia el sistema público',
        'study' => 'Estudio y orientación técnica',
        'plant_project' => 'Proyecto de planta de tratamiento',
    ],
    'catalog' => [
        'WPD' => [
            'name' => 'Un sistema web claro para tu equipo o tus clientes',
            'description' => 'Cuando el trabajo está repartido entre mensajes, hojas de cálculo y seguimientos manuales, te ayudamos a crear una plataforma enfocada que reúna la información importante en un solo lugar.',
            'deliverables' => ['plan claro de funciones', 'ruta de entrega', 'notas de lanzamiento'],
        ],
        'CRM' => [
            'name' => 'Un sistema simple para seguir y gestionar tus clientes',
            'description' => 'Te ayudamos a dar seguimiento a contactos, solicitudes, conversaciones y próximos pasos sin obligar a tu equipo a usar una herramienta pesada.',
            'deliverables' => ['mapa del flujo de clientes', 'módulos de gestión', 'guía de uso'],
        ],
        'TPM' => [
            'name' => 'Coordinación constante para tu proyecto digital',
            'description' => 'Si tu proyecto tiene muchas piezas, proveedores, decisiones y fechas, ayudamos a ordenar el trabajo para que el avance sea visible y las decisiones no se pierdan.',
            'deliverables' => ['plan de proyecto', 'ritmo de actualización', 'registro de riesgos y decisiones'],
        ],
        'TSD' => [
            'name' => 'Un plan práctico antes de invertir en software',
            'description' => 'Antes de escribir código, ayudamos a definir qué debe construirse, qué puede esperar y cómo debería funcionar el sistema para evitar confusiones costosas después.',
            'deliverables' => ['notas de solución', 'alcance claro del proyecto', 'ruta de implementación'],
        ],
        'ADI' => ['name' => 'Distribución de agua potable dentro de tu proyecto', 'description' => 'Diseñamos la red interna de agua para que edificios, instalaciones o desarrollos cuenten con un esquema de suministro claro y técnicamente soportado.'],
        'ADE' => ['name' => 'Conexiones y redes externas de agua potable', 'description' => 'Apoyamos el diseño de redes externas y conexiones de agua para que tu proyecto pueda integrarse de forma responsable al sistema existente.'],
        'SSI' => ['name' => 'Recolección de aguas residuales dentro de tu proyecto', 'description' => 'Diseñamos sistemas internos de aguas residuales para que el proyecto recoja y conduzca los caudales sanitarios de forma segura, clara y técnicamente adecuada.'],
        'SSE' => ['name' => 'Sistemas externos de recolección de aguas residuales', 'description' => 'Ayudamos a estructurar redes externas y rutas de recolección para proyectos que necesitan infraestructura sanitaria confiable.'],
        'SLI' => ['name' => 'Drenaje de aguas lluvias dentro de tu proyecto', 'description' => 'Diseñamos sistemas internos de drenaje para manejar el agua lluvia y reducir riesgos de operación, construcción e inundación.'],
        'SLE' => ['name' => 'Redes externas de drenaje de aguas lluvias', 'description' => 'Apoyamos el diseño de redes de aguas lluvias para vías, desarrollos y zonas cercanas que necesitan manejo ordenado de escorrentía.'],
        'FPN' => ['name' => 'Red hidráulica para protección contra incendios', 'description' => 'Ayudamos a definir la red de agua necesaria para cumplir los requerimientos de protección contra incendios con cálculos técnicos y planos claros.'],
        'HYD' => ['name' => 'Estudios de lluvia, caudales y cuencas', 'description' => 'Analizamos lluvias, caudales, cuencas y comportamiento de escorrentía para que tu proyecto tome mejores decisiones de diseño y riesgo.'],
        'PTP' => ['name' => 'Planeación y diseño de plantas de tratamiento de agua potable', 'description' => 'Apoyamos el diseño técnico y la documentación necesaria para proyectos que tratan agua para uso y suministro seguro.'],
        'PTR' => ['name' => 'Planeación y diseño de plantas de tratamiento de aguas residuales', 'description' => 'Ayudamos a estructurar el diseño técnico y la documentación de proyectos que tratan aguas residuales antes de su descarga o reutilización.'],
    ],
];
