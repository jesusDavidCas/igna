<?php

namespace Database\Seeders;

use App\Models\ProposalServiceTemplate;
use Illuminate\Database\Seeder;

class ProposalServiceTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $index => $payload) {
            $items = $payload['items'];
            unset($payload['items']);

            $template = ProposalServiceTemplate::query()->updateOrCreate(
                ['code' => $payload['code']],
                [
                    ...$payload,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ],
            );

            $template->items()->delete();

            foreach ($items as $itemIndex => $item) {
                $template->items()->create([
                    ...$item,
                    'sort_order' => $itemIndex + 1,
                ]);
            }
        }
    }

    private function templates(): array
    {
        return [
            [
                'code' => 'ENG-001',
                'service_number' => 1,
                'name_es' => 'Agua potable para tu proyecto',
                'name_en' => 'Drinking water for your project',
                'landing_title_es' => 'Llevar agua potable a cada punto del proyecto',
                'landing_title_en' => 'Bring drinking water to every point in your project',
                'landing_description_es' => 'Organizamos cómo debe distribuirse el agua dentro del edificio, predio o desarrollo para que el suministro sea eficiente, seguro y fácil de mantener.',
                'landing_description_en' => 'We organize how water should be distributed inside the building, property, or development so the supply is efficient, safe, and easier to maintain.',
                'items' => [
                    [
                        'item_code' => '1',
                        'description_es' => 'Diseño hidrosanitario del proyecto XXX red de agua potable, incluye cantidades de materiales y detalles. Todo en formato DWG.',
                        'description_en' => 'Hydrosanitary design for project XXX drinking water network, including material quantities and details. Delivered in DWG format.',
                        'unit' => 'Und',
                        'quantity' => 1,
                        'unit_value' => null,
                    ],
                ],
            ],
            [
                'code' => 'ENG-002',
                'service_number' => 2,
                'name_es' => 'Agua potable para tu proyecto',
                'name_en' => 'Drinking water for your project',
                'landing_title_es' => 'Conectar tu proyecto al sistema de agua público',
                'landing_title_en' => 'Connect your project to the public water system',
                'landing_description_es' => 'Te ayudamos a resolver la conexión exterior de agua potable y a preparar una propuesta técnica coherente con el entorno del proyecto.',
                'landing_description_en' => 'We help solve the exterior drinking water connection and prepare a technical proposal that fits the project surroundings.',
                'items' => [
                    [
                        'item_code' => '1',
                        'description_es' => 'Levantamiento topográfico con RTK, con las especificaciones que exige el ministerio, incluye instalación de mojones cada 500 m.',
                        'description_en' => 'RTK topographic survey following ministry specifications, including marker installation every 500 m.',
                        'unit' => 'km',
                        'quantity' => null,
                        'unit_value' => null,
                    ],
                    [
                        'item_code' => '2',
                        'description_es' => 'Diseño hidráulico del proyecto de la red de acueducto del municipio XXXX, incluye red de aducción, conducción y distribución hacia los usuarios, cuadros de cálculo, red diseñada en EPANET y planos hidráulicos terminados.',
                        'description_en' => 'Hydraulic design for the municipal aqueduct network project XXXX, including adduction, conveyance and distribution networks, calculation tables, EPANET model, and completed hydraulic plans.',
                        'unit' => 'Und',
                        'quantity' => 1,
                        'unit_value' => null,
                    ],
                    [
                        'item_code' => '3',
                        'description_es' => 'Cálculo y elaboración del presupuesto, APU, cantidades de obra, programación de obra, especificaciones técnicas, presupuesto de A.I.U., interventoría, factor multiplicador y cotizaciones.',
                        'description_en' => 'Budget calculation and preparation, unit price analysis, work quantities, construction schedule, technical specifications, overhead/profit budget, supervision, multiplier factor, and quotations.',
                        'unit' => 'Und',
                        'quantity' => 1,
                        'unit_value' => null,
                    ],
                ],
            ],
            [
                'code' => 'ENG-003',
                'service_number' => 3,
                'name_es' => 'Manejo seguro de aguas residuales',
                'name_en' => 'Safe wastewater handling',
                'landing_title_es' => 'Conducir las aguas residuales de forma segura',
                'landing_title_en' => 'Move wastewater safely through the project',
                'landing_description_es' => 'Definimos cómo transportar herméticamente las aguas residuales dentro del proyecto para reducir riesgos y dejar una solución técnica.',
                'landing_description_en' => 'We define how wastewater should be carried through the project in a sealed, safe way to reduce risk and leave a clear technical solution.',
                'items' => [
                    [
                        'item_code' => '1',
                        'description_es' => 'Diseño hidrosanitario del proyecto XXX red sanitaria de aguas residuales, incluye cantidades de materiales y detalles. Todo en formato DWG.',
                        'description_en' => 'Hydrosanitary design for project XXX sanitary wastewater network, including material quantities and details. Delivered in DWG format.',
                        'unit' => 'Und',
                        'quantity' => 1,
                        'unit_value' => null,
                    ],
                ],
            ],
            [
                'code' => 'ENG-004',
                'service_number' => 4,
                'name_es' => 'Manejo seguro de aguas residuales',
                'name_en' => 'Safe wastewater handling',
                'landing_title_es' => 'Establecer la salida externa de aguas residuales',
                'landing_title_en' => 'Set the exterior wastewater discharge route',
                'landing_description_es' => 'Apoyamos la ruta exterior para conducir las aguas residuales hacia el punto correspondiente, cumpliendo las normas sanitarias obligatorias.',
                'landing_description_en' => 'We support the exterior route for carrying wastewater to the correct point while meeting mandatory sanitary requirements.',
                'items' => [
                    [
                        'item_code' => '1',
                        'description_es' => 'Levantamiento topográfico con RTK, con las especificaciones que exige el ministerio, incluye instalación de mojones cada 500 m.',
                        'description_en' => 'RTK topographic survey following ministry specifications, including marker installation every 500 m.',
                        'unit' => 'km',
                        'quantity' => null,
                        'unit_value' => null,
                    ],
                    [
                        'item_code' => '2',
                        'description_es' => 'Diseño hidráulico de alcantarillado sanitario.',
                        'description_en' => 'Hydraulic design of the sanitary sewer system.',
                        'unit' => 'Und',
                        'quantity' => 1,
                        'unit_value' => null,
                    ],
                    [
                        'item_code' => '3',
                        'description_es' => 'Cálculo y elaboración del presupuesto, APU, cantidades de obra, programación de obra, especificaciones técnicas, presupuesto de A.I.U., interventoría, factor multiplicador y cotizaciones.',
                        'description_en' => 'Budget calculation and preparation, unit price analysis, work quantities, construction schedule, technical specifications, overhead/profit budget, supervision, multiplier factor, and quotations.',
                        'unit' => 'Und',
                        'quantity' => 1,
                        'unit_value' => null,
                    ],
                ],
            ],
            [
                'code' => 'ENG-005',
                'service_number' => 5,
                'name_es' => 'Control del agua lluvia',
                'name_en' => 'Rainwater control',
                'landing_title_es' => 'Evitar problemas por lluvia dentro del proyecto',
                'landing_title_en' => 'Avoid rainwater problems inside the project',
                'landing_description_es' => 'Te ayudamos a organizar el manejo del agua lluvia dentro del predio para reducir acumulaciones, daños, retrasos o riesgos de inundación.',
                'landing_description_en' => 'We help organize rainwater management inside the property to reduce pooling, damage, delays, or flooding risk.',
                'items' => [
                    [
                        'item_code' => '1',
                        'description_es' => 'Diseño hidrosanitario del proyecto XX red de aguas lluvias, incluye cantidades de materiales y detalles. Todo en formato DWG.',
                        'description_en' => 'Hydrosanitary design for project XX stormwater network, including material quantities and details. Delivered in DWG format.',
                        'unit' => 'Und',
                        'quantity' => 1,
                        'unit_value' => null,
                    ],
                ],
            ],
            [
                'code' => 'ENG-006',
                'service_number' => 6,
                'name_es' => 'Control del agua lluvia',
                'name_en' => 'Rainwater control',
                'landing_title_es' => 'Manejar el agua lluvia alrededor del proyecto',
                'landing_title_en' => 'Manage rainwater around the project',
                'landing_description_es' => 'Organizamos la conducción exterior del agua lluvia para que vías, zonas comunes o desarrollos tengan una solución ordenada.',
                'landing_description_en' => 'We organize the exterior rainwater route so roads, shared areas, or developments have a clear and orderly solution.',
                'items' => [
                    [
                        'item_code' => '1',
                        'description_es' => 'Levantamiento topográfico con RTK, con las especificaciones que exige el ministerio, incluye instalación de mojones cada 500 m.',
                        'description_en' => 'RTK topographic survey following ministry specifications, including marker installation every 500 m.',
                        'unit' => 'km',
                        'quantity' => null,
                        'unit_value' => null,
                    ],
                    [
                        'item_code' => '2',
                        'description_es' => 'Diseño hidráulico de alcantarillado pluvial.',
                        'description_en' => 'Hydraulic design of the stormwater sewer system.',
                        'unit' => 'Und',
                        'quantity' => 1,
                        'unit_value' => null,
                    ],
                    [
                        'item_code' => '3',
                        'description_es' => 'Cálculo y elaboración del presupuesto, APU, cantidades de obra, programación de obra, especificaciones técnicas, presupuesto de A.I.U., interventoría, factor multiplicador y cotizaciones.',
                        'description_en' => 'Budget calculation and preparation, unit price analysis, work quantities, construction schedule, technical specifications, overhead/profit budget, supervision, multiplier factor, and quotations.',
                        'unit' => 'Und',
                        'quantity' => 1,
                        'unit_value' => null,
                    ],
                ],
            ],
            [
                'code' => 'ENG-007',
                'service_number' => 7,
                'name_es' => 'Sistema de agua para protección contra incendios',
                'name_en' => 'Water system for fire protection',
                'landing_title_es' => 'Diseñar la red de agua para protección de incendios',
                'landing_title_en' => 'Design the water network for fire protection',
                'landing_description_es' => 'Te ayudamos a definir una solución de agua para protección contra incendios con cálculos, criterios y planos que faciliten la construcción.',
                'landing_description_en' => 'We help define a fire protection water solution with calculations, criteria, and plans that make construction easier.',
                'items' => [
                    [
                        'item_code' => '1',
                        'description_es' => 'Diseño hidráulico de la red de incendio para el proyecto XX, incluye planos, memoria de cálculo y cantidades de materiales. Todo en formato digital.',
                        'description_en' => 'Hydraulic design of the fire protection network for project XX, including plans, calculation report, and material quantities. Delivered digitally.',
                        'unit' => 'Und',
                        'quantity' => 1,
                        'unit_value' => null,
                    ],
                ],
            ],
            [
                'code' => 'ENG-008',
                'service_number' => 8,
                'name_es' => 'Entender lluvia, caudales y riesgo',
                'name_en' => 'Understanding rainfall, flows, and risk',
                'landing_title_es' => 'Proteger tus proyectos con información hidrológica precisa',
                'landing_title_en' => 'Protect your projects with precise hydrology information',
                'landing_description_es' => 'Modelamos las cuencas para que tomes decisiones con mejor información y reduzcas riesgos de subdimensionamiento desde el diseño.',
                'landing_description_en' => 'We model watersheds so you can make better-informed decisions and reduce undersizing risks from the design stage.',
                'items' => [
                    [
                        'item_code' => '1',
                        'description_es' => 'Levantamiento topográfico con RTK, con las especificaciones que exige el ministerio, incluye instalación de mojones cada 500 m.',
                        'description_en' => 'RTK topographic survey following ministry specifications, including marker installation every 500 m.',
                        'unit' => 'km',
                        'quantity' => null,
                        'unit_value' => null,
                    ],
                    [
                        'item_code' => '2',
                        'description_es' => 'Estudio hidrológico de la cuenca del municipio de Acevedo, incluye modelos en HEC-HMS y HEC-RAS.',
                        'description_en' => 'Hydrology study of the watershed in the municipality of Acevedo, including HEC-HMS and HEC-RAS models.',
                        'unit' => 'Und',
                        'quantity' => 1,
                        'unit_value' => null,
                    ],
                    [
                        'item_code' => '3',
                        'description_es' => 'Cálculo y elaboración del presupuesto, APU, cantidades de obra, programación de obra, especificaciones técnicas, presupuesto de A.I.U., interventoría, factor multiplicador y cotizaciones.',
                        'description_en' => 'Budget calculation and preparation, unit price analysis, work quantities, construction schedule, technical specifications, overhead/profit budget, supervision, multiplier factor, and quotations.',
                        'unit' => 'Und',
                        'quantity' => 1,
                        'unit_value' => null,
                    ],
                ],
            ],
            [
                'code' => 'ENG-009',
                'service_number' => 9,
                'name_es' => 'Planta de tratamiento de agua potable',
                'name_en' => 'Drinking water treatment plant',
                'landing_title_es' => 'Preparar una planta o sistema para tratar agua potable',
                'landing_title_en' => 'Prepare a plant or system to treat drinking water',
                'landing_description_es' => 'Te acompañamos en la organización técnica de una planta o sistema de tratamiento para que el agua pueda consumirse con mayor seguridad.',
                'landing_description_en' => 'We support the technical organization of a treatment plant or system so water can be consumed more safely.',
                'items' => [
                    [
                        'item_code' => '1',
                        'description_es' => 'Levantamiento topográfico con RTK, con las especificaciones que exige el ministerio, incluye instalación de mojones cada 500 m.',
                        'description_en' => 'RTK topographic survey following ministry specifications, including marker installation every 500 m.',
                        'unit' => 'km',
                        'quantity' => null,
                        'unit_value' => null,
                    ],
                    [
                        'item_code' => '2',
                        'description_es' => 'Diseño hidráulico de la planta de tratamiento de agua potable del municipio de XXXX.',
                        'description_en' => 'Hydraulic design of the drinking water treatment plant for the municipality of XXXX.',
                        'unit' => 'Und',
                        'quantity' => 1,
                        'unit_value' => null,
                    ],
                    [
                        'item_code' => '3',
                        'description_es' => 'Cálculo y elaboración del presupuesto, APU, cantidades de obra, programación de obra, especificaciones técnicas, presupuesto de A.I.U., interventoría, factor multiplicador y cotizaciones.',
                        'description_en' => 'Budget calculation and preparation, unit price analysis, work quantities, construction schedule, technical specifications, overhead/profit budget, supervision, multiplier factor, and quotations.',
                        'unit' => 'Und',
                        'quantity' => 1,
                        'unit_value' => null,
                    ],
                ],
            ],
            [
                'code' => 'ENG-010',
                'service_number' => 10,
                'name_es' => 'Planta de tratamiento de aguas residuales',
                'name_en' => 'Wastewater treatment plant',
                'landing_title_es' => 'Estructurar el tratamiento de aguas residuales',
                'landing_title_en' => 'Structure wastewater treatment',
                'landing_description_es' => 'Te ayudamos a estructurar el sistema que trate las aguas residuales antes de descargarlas o reutilizarlas, con una solución técnica de excelente calidad.',
                'landing_description_en' => 'We help structure the system that treats wastewater before discharge or reuse, with a high-quality technical solution.',
                'items' => [
                    [
                        'item_code' => '1',
                        'description_es' => 'Levantamiento topográfico con RTK, con las especificaciones que exige el ministerio, incluye instalación de mojones cada 500 m.',
                        'description_en' => 'RTK topographic survey following ministry specifications, including marker installation every 500 m.',
                        'unit' => 'km',
                        'quantity' => null,
                        'unit_value' => null,
                    ],
                    [
                        'item_code' => '2',
                        'description_es' => 'Diseño hidráulico de la planta de tratamiento de aguas residuales del municipio XXXX.',
                        'description_en' => 'Hydraulic design of the wastewater treatment plant for the municipality XXXX.',
                        'unit' => 'Und',
                        'quantity' => 1,
                        'unit_value' => null,
                    ],
                    [
                        'item_code' => '3',
                        'description_es' => 'Cálculo y elaboración del presupuesto, APU, cantidades de obra, programación de obra, especificaciones técnicas, presupuesto de A.I.U., interventoría, factor multiplicador y cotizaciones.',
                        'description_en' => 'Budget calculation and preparation, unit price analysis, work quantities, construction schedule, technical specifications, overhead/profit budget, supervision, multiplier factor, and quotations.',
                        'unit' => 'Und',
                        'quantity' => 1,
                        'unit_value' => null,
                    ],
                ],
            ],
        ];
    }
}
