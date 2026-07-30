<?php

return [
    'types' => [
        'web_platform' => 'Un lugar claro para gestionar trabajo y clientes',
        'crm' => 'Seguimiento simple de clientes y solicitudes',
        'project_management' => 'Acompañamiento para que tu proyecto no se pierda',
        'technical_structuring' => 'Ordenar una idea digital antes de construirla',
        'aqueduct' => 'Agua potable para tu proyecto',
        'sanitary_sewer' => 'Manejo seguro de aguas residuales',
        'stormwater_sewer' => 'Control del agua lluvia',
        'fire_protection' => 'Agua disponible para protección contra incendios',
        'hydrology' => 'Entender lluvia, caudales y riesgo',
        'ptap' => 'Agua tratada para consumo o uso seguro',
        'ptar' => 'Tratamiento de aguas residuales',
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
        'ADI' => ['name' => 'Llevar agua potable a cada punto del proyecto', 'description' => 'Organizamos cómo debe distribuirse el agua dentro del edificio, predio o desarrollo para que el suministro sea eficiente, seguro y fácil de mantener.', 'deliverables' => ['memoria descriptiva del proyecto', 'informe de cálculo de redes', 'planos hidráulicos', 'referencias de la Resolución 799 de 2021']],
        'ADE' => ['name' => 'Conectar tu proyecto al sistema de agua público', 'description' => 'Te ayudamos a resolver la conexión exterior de agua potable y a preparar una propuesta técnica coherente con el entorno del proyecto.', 'deliverables' => ['memoria descriptiva del proyecto', 'informe de cálculo de redes', 'planos hidráulicos', 'referencias de la Resolución 799 de 2021']],
        'SSI' => ['name' => 'Conducir las aguas residuales de forma segura', 'description' => 'Definimos cómo transportar herméticamente las aguas residuales dentro del proyecto para reducir riesgos y dejar una solución técnica clara.', 'deliverables' => ['memoria descriptiva del proyecto', 'informe de cálculo de redes', 'planos sanitarios', 'referencias de la Resolución 799 de 2021']],
        'SSE' => ['name' => 'Establecer la salida externa de aguas residuales', 'description' => 'Apoyamos la ruta exterior para conducir las aguas residuales hacia el punto correspondiente, cumpliendo las normas sanitarias obligatorias.', 'deliverables' => ['memoria descriptiva del proyecto', 'informe de cálculo de redes', 'planos sanitarios', 'referencias de la Resolución 799 de 2021']],
        'SLI' => ['name' => 'Evitar problemas por lluvia dentro del proyecto', 'description' => 'Diseñamos la red de aguas lluvias dentro del proyecto para reducir acumulaciones, daños y riesgos de inundación.', 'deliverables' => ['memoria descriptiva del proyecto', 'cálculos de drenaje', 'planos de aguas lluvias', 'referencias de la Resolución 799 de 2021']],
        'SLE' => ['name' => 'Manejar el agua lluvia alrededor del proyecto', 'description' => 'Organizamos la conducción exterior del agua lluvia para que vías, zonas comunes o desarrollos tengan una solución ordenada.', 'deliverables' => ['memoria descriptiva del proyecto', 'cálculos de drenaje', 'planos de aguas lluvias', 'referencias de la Resolución 799 de 2021']],
        'FPN' => ['name' => 'Diseñar la red de agua para protección de incendios', 'description' => 'Te ayudamos a definir una solución de agua para protección contra incendios con cálculos, criterios y planos que faciliten la construcción.', 'deliverables' => ['memoria descriptiva del proyecto', 'cálculos de redes', 'planos hidráulicos', 'referencias de la Resolución 799 de 2021', 'referencia NSR10']],
        'HYD' => ['name' => 'Entender lluvia, caudales y riesgo', 'description' => 'Modelamos las cuencas para que tomes decisiones con mejor información y reduzcas riesgos de subdimensionamiento desde el diseño.', 'deliverables' => ['memoria descriptiva del proyecto', 'cálculos de caudal', 'planos de cuenca', 'modelos HEC-HMS y HEC-RAS']],
        'PTP' => ['name' => 'Preparar una planta o sistema para tratar agua potable', 'description' => 'Te acompañamos en la organización técnica de una planta o sistema de tratamiento para que el agua pueda consumirse con mayor seguridad.', 'deliverables' => ['memoria descriptiva del proyecto', 'cálculos hidráulicos', 'planos técnicos', 'referencias de la Resolución 799 de 2021']],
        'PTR' => ['name' => 'Estructurar el tratamiento de aguas residuales', 'description' => 'Te ayudamos a estructurar el sistema que trate las aguas residuales antes de descargarlas o reutilizarlas, con una solución técnica de excelente calidad.', 'deliverables' => ['memoria descriptiva del proyecto', 'informes de cálculo de redes', 'planos hidráulicos', 'referencias de la Resolución 799 de 2021']],
    ],
];
