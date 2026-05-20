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
        'ADI' => ['name' => 'Llevar agua potable a cada punto del proyecto', 'description' => 'Organizamos cómo debe moverse el agua dentro del edificio, predio o desarrollo para que el suministro sea claro, seguro y fácil de revisar.'],
        'ADE' => ['name' => 'Conectar tu proyecto al sistema de agua', 'description' => 'Te ayudamos a resolver la conexión exterior de agua potable y a preparar una propuesta técnica coherente con el entorno del proyecto.'],
        'SSI' => ['name' => 'Sacar las aguas residuales de forma segura', 'description' => 'Definimos cómo recoger y conducir las aguas residuales dentro del proyecto para reducir riesgos y dejar una solución bien documentada.'],
        'SSE' => ['name' => 'Organizar la salida externa de aguas residuales', 'description' => 'Apoyamos la ruta exterior para conducir las aguas residuales hacia el punto correspondiente, con claridad para revisión y ejecución.'],
        'SLI' => ['name' => 'Evitar problemas por agua lluvia dentro del proyecto', 'description' => 'Te ayudamos a manejar el agua lluvia dentro del predio para reducir acumulaciones, daños, retrasos o riesgos de inundación.'],
        'SLE' => ['name' => 'Manejar el agua lluvia alrededor del proyecto', 'description' => 'Organizamos la conducción exterior del agua lluvia para que vías, zonas comunes o desarrollos tengan una solución ordenada.'],
        'FPN' => ['name' => 'Preparar la red de agua para protección contra incendios', 'description' => 'Te ayudamos a definir una solución de agua para protección contra incendios con cálculos, criterios y planos que faciliten la revisión.'],
        'HYD' => ['name' => 'Entender cómo se comporta el agua en el terreno', 'description' => 'Analizamos lluvia, caudales y cuencas para que tomes decisiones con mejor información y reduzcas riesgos desde el diseño.'],
        'PTP' => ['name' => 'Preparar un proyecto para tratar agua potable', 'description' => 'Te acompañamos en la organización técnica de una planta o sistema de tratamiento para que el agua pueda usarse con mayor seguridad.'],
        'PTR' => ['name' => 'Preparar un proyecto para tratar aguas residuales', 'description' => 'Te ayudamos a estructurar el sistema que trata las aguas residuales antes de descargarlas o reutilizarlas, con entregables claros.'],
    ],
];
