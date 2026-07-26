<?php

namespace Database\Seeders;

use App\Enums\BlogPostStatus;
use App\Enums\UserRole;
use App\Models\BlogPost;
use App\Models\Service;
use App\Models\ServiceStage;
use App\Models\Ticket;
use App\Models\TicketFile;
use App\Models\User;
use App\Services\Tickets\TicketLifecycleService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'operaciones@ignastudio.test'],
            [
                'first_name' => 'IGNA',
                'last_name' => 'Operaciones',
                'phone' => '+57 300 000 1000',
                'preferred_language' => 'es',
                'role' => UserRole::ADMIN,
                'is_active' => true,
                'password' => 'Password123!',
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'jesus@ignastudio.test'],
            [
                'first_name' => 'Jesús David',
                'last_name' => 'Castañeda',
                'phone' => '+57 300 000 1001',
                'preferred_language' => 'es',
                'role' => UserRole::ADMIN,
                'is_active' => true,
                'password' => 'Password123!',
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'roberto@ignastudio.test'],
            [
                'first_name' => 'Roberto',
                'last_name' => 'Castañeda Pardo',
                'phone' => '+57 300 000 1002',
                'preferred_language' => 'es',
                'role' => UserRole::ADMIN,
                'is_active' => true,
                'password' => 'Password123!',
            ],
        );

        $clients = [
            'digital' => User::query()->updateOrCreate(
                ['email' => 'cliente.digital@ignastudio.test'],
                [
                    'first_name' => 'Laura',
                    'last_name' => 'Martinez',
                    'phone' => '+57 300 222 3344',
                    'preferred_language' => 'es',
                    'role' => UserRole::CLIENT,
                    'is_active' => true,
                    'password' => 'Password123!',
                ],
            ),
            'engineering' => User::query()->updateOrCreate(
                ['email' => 'cliente.infra@ignastudio.test'],
                [
                    'first_name' => 'Carlos',
                    'last_name' => 'Rincon',
                    'phone' => '+57 301 555 8899',
                    'preferred_language' => 'es',
                    'role' => UserRole::CLIENT,
                    'is_active' => true,
                    'password' => 'Password123!',
                ],
            ),
        ];

        $this->seedBlogPosts($admin);
        $this->seedTickets($admin, $clients);
    }

    private function seedBlogPosts(User $admin): void
    {
        $posts = [
            [
                'slug' => 'plataformas-digitales-que-si-ordenan-el-trabajo',
                'title' => 'Plataformas digitales que sí ordenan el trabajo diario',
                'summary' => 'Una buena plataforma no debe sentirse pesada: debe ayudar al equipo a saber qué sigue, quién responde y dónde están los archivos.',
                'body_html' => '<p>Muchas empresas empiezan gestionando clientes, solicitudes y entregables entre chats, correos y hojas sueltas. Al principio funciona, pero con el tiempo aparecen pérdidas de información, reprocesos y preguntas repetidas.</p><p>Una plataforma digital bien diseñada no intenta reemplazar toda la operación. Primero ordena lo esencial: solicitudes, responsables, etapas, archivos y comunicación con el cliente.</p><p>El resultado no es solo tecnología. Es más claridad para vender, ejecutar y responder con confianza.</p>',
                'status' => BlogPostStatus::PUBLISHED,
                'published_at' => now()->subDays(8),
                'seo_keywords' => ['plataformas digitales', 'seguimiento de proyectos', 'gestion de clientes'],
            ],
            [
                'slug' => 'infraestructura-hidrica-con-documentacion-clara',
                'title' => 'Infraestructura hídrica con documentación clara desde el inicio',
                'summary' => 'En proyectos de acueducto, saneamiento o tratamiento, la calidad técnica también depende de cómo se organiza la información.',
                'body_html' => '<p>Un proyecto hídrico no avanza solo con cálculos. También necesita trazabilidad: memorias, planos, criterios, versiones, observaciones y entregables claros.</p><p>Cuando la documentación se organiza por etapas y entregables, el cliente entiende mejor el avance y el equipo técnico reduce el riesgo de omitir información crítica.</p><p>Por eso conectamos diseño técnico con seguimiento operativo: cada archivo debe tener contexto, estado y propósito.</p>',
                'status' => BlogPostStatus::PUBLISHED,
                'published_at' => now()->subDays(3),
                'seo_keywords' => ['infraestructura hidrica', 'acueducto', 'saneamiento'],
            ],
            [
                'slug' => 'cotizaciones-tecnicas-que-ayudan-a-decidir',
                'title' => 'Cotizaciones técnicas que ayudan a decidir, no solo a comparar precios',
                'summary' => 'Una propuesta clara explica alcance, tiempos, pagos y entregables para que el cliente entienda qué está comprando.',
                'body_html' => '<p>Una cotización técnica no debería ser una tabla fría de precios. Debe explicar el problema, el alcance, la ruta de trabajo y los entregables que el cliente recibirá.</p><p>Cuando una propuesta está bien estructurada, reduce malentendidos antes de iniciar el proyecto y facilita aprobar, ajustar o priorizar el trabajo.</p><p>El precio importa, pero la claridad del alcance es lo que protege la relación durante la ejecución.</p>',
                'status' => BlogPostStatus::PUBLISHED,
                'published_at' => now()->subDay(),
                'seo_keywords' => ['cotizaciones tecnicas', 'propuestas', 'estructuracion de proyectos'],
            ],
            [
                'slug' => 'trazabilidad-operativa-en-servicios-tecnicos',
                'title' => 'Trazabilidad operativa en servicios técnicos',
                'summary' => 'Cómo una estructura simple de solicitudes, etapas y archivos mejora la coordinación con clientes.',
                'body_html' => '<p>Una plataforma ligera puede ordenar solicitudes, responsables y entregables sin convertir la operación en un sistema pesado.</p><p>La clave está en mantener servicios configurables, etapas claras y visibilidad controlada para cada cliente.</p>',
                'status' => BlogPostStatus::DRAFT,
                'published_at' => null,
                'seo_keywords' => ['operacion', 'tickets', 'servicios tecnicos'],
            ],
        ];

        foreach ($posts as $post) {
            BlogPost::query()->updateOrCreate(
                ['slug' => $post['slug']],
                [
                    ...$post,
                    'created_by_user_id' => $admin->id,
                    'updated_by_user_id' => $admin->id,
                ],
            );
        }
    }

    /**
     * Demo tickets intentionally cover digital, engineering, files, clients, and stages
     * so the dashboard indicators and portals have realistic data during manual testing.
     */
    private function seedTickets(User $admin, array $clients): void
    {
        $lifecycle = app(TicketLifecycleService::class);

        Ticket::query()
            ->where('project_name', 'like', 'Prefactibilidad%rural')
            ->update([
                'project_name' => 'Prefactibilidad planta de tratamiento de agua potable rural',
                'project_description' => 'Estructuración técnica inicial para proyecto de planta de tratamiento de agua potable rural y revisión de requerimientos documentales.',
            ]);

        $tickets = [
            [
                'service_code' => 'WPD',
                'client' => $clients['digital'],
                'first_name' => 'Laura',
                'last_name' => 'Martinez',
                'email' => 'cliente.digital@ignastudio.test',
                'phone' => '+57 300 222 3344',
                'project_name' => 'Portal de seguimiento comercial',
                'project_location' => 'Bogota',
                'preferred_language' => 'es',
                'project_description' => 'Portal ligero para centralizar solicitudes comerciales, estados y entregables disponibles para clientes.',
                'target_date' => now()->addWeeks(4)->toDateString(),
                'stage_code' => 'STR',
                'files' => [
                    ['title' => 'Alcance funcional inicial', 'original_name' => 'alcance-funcional.txt', 'visible' => true, 'deliverable_type' => 'functional_scope'],
                    ['title' => 'Notas internas de arquitectura', 'original_name' => 'notas-arquitectura.txt', 'visible' => false, 'deliverable_type' => 'technical_notes'],
                ],
            ],
            [
                'service_code' => 'ADI',
                'client' => $clients['engineering'],
                'first_name' => 'Carlos',
                'last_name' => 'Rincon',
                'email' => 'cliente.infra@ignastudio.test',
                'phone' => '+57 301 555 8899',
                'project_name' => 'Red interna de acueducto institucional',
                'project_location' => 'Cundinamarca',
                'preferred_language' => 'es',
                'project_description' => 'Diseño hidráulico para red interna de acueducto con memoria descriptiva, cálculos y planos.',
                'target_date' => now()->addWeeks(6)->toDateString(),
                'stage_code' => 'DSN',
                'files' => [
                    ['title' => 'Memoria descriptiva preliminar', 'original_name' => 'memoria-descriptiva.txt', 'visible' => true, 'deliverable_type' => 'descriptive_report'],
                    ['title' => 'Cálculos de red', 'original_name' => 'calculos-red.txt', 'visible' => true, 'deliverable_type' => 'network_calculations'],
                ],
            ],
            [
                'service_code' => 'HYD',
                'client' => null,
                'first_name' => 'Andres',
                'last_name' => 'Gomez',
                'email' => 'andres.gomez@example.com',
                'phone' => '+57 310 444 7788',
                'project_name' => 'Estudio hidrologico de microcuenca',
                'project_location' => 'Antioquia',
                'preferred_language' => 'es',
                'project_description' => 'Solicitud inicial para revisar caudales, delimitación de microcuenca y modelos lluvia-escorrentía.',
                'target_date' => now()->addWeeks(8)->toDateString(),
                'stage_code' => 'REV',
                'files' => [
                    ['title' => 'Mapa base de microcuenca', 'original_name' => 'microcuenca-base.txt', 'visible' => false, 'deliverable_type' => 'basin_plan'],
                ],
            ],
            [
                'service_code' => 'PTP',
                'client' => $clients['engineering'],
                'first_name' => 'Carlos',
                'last_name' => 'Rincon',
                'email' => 'cliente.infra@ignastudio.test',
                'phone' => '+57 301 555 8899',
                'project_name' => 'Prefactibilidad planta de tratamiento de agua potable rural',
                'project_location' => 'Boyaca',
                'preferred_language' => 'es',
                'project_description' => 'Estructuración técnica inicial para proyecto de planta de tratamiento de agua potable rural y revisión de requerimientos documentales.',
                'target_date' => now()->addWeeks(10)->toDateString(),
                'stage_code' => 'INT',
                'files' => [],
            ],
        ];

        foreach ($tickets as $ticketPayload) {
            $service = Service::query()->where('code', $ticketPayload['service_code'])->first();

            if (! $service) {
                continue;
            }

            $ticket = Ticket::query()
                ->where('project_name', $ticketPayload['project_name'])
                ->where('email', $ticketPayload['email'])
                ->first();

            if (! $ticket) {
                $ticket = $lifecycle->createFromPublicRequest([
                    ...collect($ticketPayload)->except(['service_code', 'client', 'stage_code', 'files'])->all(),
                    'service_id' => $service->id,
                ], notify: false);
            }

            $ticket->forceFill([
                'client_user_id' => $ticketPayload['client']?->id,
                'google_drive_folder_id' => "demo-folder-{$ticket->ticket_code}",
                'google_drive_folder_url' => "https://drive.google.com/drive/folders/demo-{$ticket->ticket_code}",
            ])->save();

            $targetStage = $service->stages()->where('code', $ticketPayload['stage_code'])->first();

            if ($targetStage) {
                $this->advanceDemoTicketToStage($lifecycle, $ticket, $targetStage, $admin);
            }

            foreach ($ticketPayload['files'] as $filePayload) {
                $storagePath = "stubs/tickets/{$ticket->ticket_code}/demo-".str($filePayload['original_name'])->slug().'.txt';

                Storage::disk('local')->put(
                    $storagePath,
                    "IGNA Studio demo file\n\nTicket: {$ticket->ticket_code}\nProject: {$ticketPayload['project_name']}\nDeliverable: {$filePayload['title']}\n",
                );

                TicketFile::query()->updateOrCreate(
                    [
                        'ticket_id' => $ticket->id,
                        'title' => $filePayload['title'],
                    ],
                    [
                        'uploaded_by_user_id' => $admin->id,
                        'original_name' => $filePayload['original_name'],
                        'stored_name' => basename($storagePath),
                        'mime_type' => 'text/plain',
                        'size_bytes' => Storage::disk('local')->size($storagePath),
                        'storage_provider' => 'local_stub',
                        'storage_disk' => 'local',
                        'storage_path' => $storagePath,
                        'google_drive_file_id' => null,
                        'google_drive_url' => null,
                        'deliverable_type' => $filePayload['deliverable_type'],
                        'is_client_visible' => $filePayload['visible'],
                        'watermark_status' => 'not_applicable',
                        'uploaded_at' => now()->subDays(2),
                    ],
                );
            }
        }
    }

    private function advanceDemoTicketToStage(TicketLifecycleService $lifecycle, Ticket $ticket, ServiceStage $targetStage, User $admin): void
    {
        $ticket = $ticket->fresh(['service.stages', 'stageEvents.serviceStage']);
        $orderedStages = $ticket->service->stages()->where('is_active', true)->orderBy('sort_order')->get()->values();
        $targetIndex = $orderedStages->search(fn (ServiceStage $stage): bool => $stage->id === $targetStage->id);

        while ($targetIndex !== false) {
            $ticket = $ticket->fresh(['service.stages', 'stageEvents.serviceStage']);
            $currentIndex = $orderedStages->search(fn (ServiceStage $stage): bool => $stage->id === $ticket->current_service_stage_id);

            if ($currentIndex === false || $currentIndex >= $targetIndex) {
                break;
            }

            $event = $ticket->stageEvents->firstWhere('service_stage_id', $ticket->current_service_stage_id);

            if (! $event) {
                break;
            }

            $lifecycle->completeStage($ticket, $event, $admin, 'Demo data for platform validation.', notify: false);
        }
    }
}
