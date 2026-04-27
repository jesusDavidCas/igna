<?php

namespace Database\Seeders;

use App\Enums\BlogPostStatus;
use App\Enums\UserRole;
use App\Models\BlogPost;
use App\Models\Service;
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
                'slug' => 'trazabilidad-operativa-en-servicios-tecnicos',
                'title' => 'Trazabilidad operativa en servicios técnicos',
                'summary' => 'Cómo una estructura simple de solicitudes, etapas y archivos mejora la coordinación con clientes.',
                'body_html' => '<p>Una plataforma ligera puede ordenar solicitudes, responsables y entregables sin convertir la operación en un sistema pesado.</p><p>La clave está en mantener servicios configurables, etapas claras y visibilidad controlada para cada cliente.</p>',
                'status' => BlogPostStatus::PUBLISHED,
                'published_at' => now()->subDays(8),
                'seo_keywords' => ['operacion', 'tickets', 'servicios tecnicos'],
            ],
            [
                'slug' => 'servicios-configurables-para-crecer-sin-reescribir',
                'title' => 'Servicios configurables para crecer sin reescribir',
                'summary' => 'La base de IGNA Studio permite agregar servicios y flujos desde el panel administrativo.',
                'body_html' => '<p>Cuando cada servicio administra su propio flujo, el negocio puede crecer sin depender de cambios de código para cada nueva oferta.</p>',
                'status' => BlogPostStatus::PUBLISHED,
                'published_at' => now()->subDays(3),
                'seo_keywords' => ['servicios', 'flujos', 'laravel'],
            ],
            [
                'slug' => 'integracion-google-drive-entregables',
                'title' => 'Integración con Google Drive para entregables',
                'summary' => 'Nota interna sobre la siguiente capa de almacenamiento documental.',
                'body_html' => '<p>La integración final conectará carpetas de proyecto y archivos disponibles para cliente con Google Drive como backend documental.</p>',
                'status' => BlogPostStatus::DRAFT,
                'published_at' => null,
                'seo_keywords' => ['google drive', 'archivos', 'entregables'],
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
                ]);
            }

            $ticket->forceFill([
                'client_user_id' => $ticketPayload['client']?->id,
                'google_drive_folder_id' => "demo-folder-{$ticket->ticket_code}",
                'google_drive_folder_url' => "https://drive.google.com/drive/folders/demo-{$ticket->ticket_code}",
            ])->save();

            $targetStage = $service->stages()->where('code', $ticketPayload['stage_code'])->first();

            if ($targetStage) {
                $lifecycle->moveToStage($ticket->fresh(['service']), $targetStage, $admin, 'Demo data for platform validation.');
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
}
