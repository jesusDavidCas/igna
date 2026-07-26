<?php

namespace Tests\Feature;

use App\Enums\StageEventStatus;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Mail\AdminNewTicketMail;
use App\Mail\ProjectUpdateMail;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\TicketFile;
use App\Models\TicketStageAudit;
use App\Models\TicketStageEvent;
use App\Models\User;
use App\Services\Notifications\ProjectNotificationService;
use App\Services\Tickets\TicketLifecycleService;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TicketWorkflowIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Mail::fake();
        $this->seed();

        $this->admin = User::query()->where('role', UserRole::SUPER_ADMIN)->firstOrFail();
    }

    public function test_spanish_and_english_request_confirmations_use_ticket_locale_not_session_locale(): void
    {
        $spanishTicket = $this->createTicket('es', 'spanish.client@example.com', 'Solicitud en español');
        $englishTicket = $this->withSession(['locale' => 'es'])
            ->createTicket('en', 'english.client@example.com', 'English request');

        Mail::assertSent(ProjectUpdateMail::class, function (ProjectUpdateMail $mail) use ($spanishTicket): bool {
            return $mail->ticket->is($spanishTicket)
                && $mail->locale === 'es'
                && $mail->headline === 'Recibimos tu solicitud'
                && str_contains((string) $mail->updateMessage, 'Tu solicitud quedó registrada');
        });

        Mail::assertSent(ProjectUpdateMail::class, function (ProjectUpdateMail $mail) use ($englishTicket): bool {
            return $mail->ticket->is($englishTicket)
                && $mail->locale === 'en'
                && $mail->headline === 'We received your request'
                && str_contains((string) $mail->updateMessage, 'Your request was registered');
        });
    }

    public function test_project_update_locale_falls_back_safely_and_does_not_leak_between_recipients(): void
    {
        $spanishTicket = $this->createTicket('es', 'updates.es@example.com', 'Actualizacion ES');
        $englishTicket = $this->createTicket('en', 'updates.en@example.com', 'Update EN');
        $invalidLocaleTicket = $this->createTicket('es', 'updates.invalid@example.com', 'Invalid locale');
        $invalidLocaleTicket->forceFill(['preferred_language' => 'fr'])->save();

        Mail::fake();
        App::setLocale('es');

        app(ProjectNotificationService::class)->notifyTicket(
            $spanishTicket,
            'file_available',
            'site.email_file_available_headline',
            messageKey: 'site.email_file_available_message',
            messageReplacements: ['file' => 'Plano A'],
        );
        app(ProjectNotificationService::class)->notifyTicket(
            $englishTicket,
            'file_available',
            'site.email_file_available_headline',
            messageKey: 'site.email_file_available_message',
            messageReplacements: ['file' => 'Plan B'],
        );
        app(ProjectNotificationService::class)->notifyTicket(
            $invalidLocaleTicket,
            'request_received',
            'site.email_request_received_headline',
            messageKey: 'site.email_request_received_message',
            messageReplacements: ['ticket' => $invalidLocaleTicket->ticket_code],
        );

        Mail::assertSent(ProjectUpdateMail::class, fn (ProjectUpdateMail $mail): bool => $mail->ticket->is($spanishTicket)
            && $mail->locale === 'es'
            && $mail->headline === 'Hay un nuevo archivo disponible'
            && str_contains((string) $mail->updateMessage, 'ya está disponible'));

        Mail::assertSent(ProjectUpdateMail::class, fn (ProjectUpdateMail $mail): bool => $mail->ticket->is($englishTicket)
            && $mail->locale === 'en'
            && $mail->headline === 'A new file is available'
            && str_contains((string) $mail->updateMessage, 'is now available'));

        Mail::assertSent(ProjectUpdateMail::class, fn (ProjectUpdateMail $mail): bool => $mail->ticket->is($invalidLocaleTicket)
            && $mail->locale === 'en'
            && $mail->headline === 'We received your request');
    }

    public function test_admin_new_ticket_email_uses_each_admin_recipient_locale(): void
    {
        $englishAdmin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin.english@example.com',
            'preferred_language' => 'en',
            'is_active' => true,
        ]);
        $spanishTicket = $this->createTicket('es', 'admin.locale.client@example.com', 'Admin locale source');

        Mail::fake();
        App::setLocale('es');

        app(ProjectNotificationService::class)->notifyAdminsNewTicket($spanishTicket);

        Mail::assertSent(AdminNewTicketMail::class, fn (AdminNewTicketMail $mail): bool => $mail->hasTo($englishAdmin->email)
            && $mail->locale === 'en');
        Mail::assertSent(AdminNewTicketMail::class, fn (AdminNewTicketMail $mail): bool => $mail->hasTo($this->admin->email)
            && $mail->locale === $this->admin->preferred_language);
    }

    public function test_subject_and_body_share_the_resolved_mail_locale(): void
    {
        $ticket = $this->createTicket('en', 'subject.body@example.com', 'Subject body consistency');

        Mail::fake();
        app(ProjectNotificationService::class)->notifyTicket(
            $ticket,
            'stage_completed',
            'site.email_stage_completed_headline',
            ['stage' => $ticket->fresh('currentStage')->currentStage],
        );

        Mail::assertSent(ProjectUpdateMail::class, function (ProjectUpdateMail $mail): bool {
            $html = $mail->render();
            $previousLocale = App::getLocale();
            App::setLocale($mail->locale);
            $subject = $mail->envelope()->subject;
            App::setLocale($previousLocale);

            return $mail->locale === 'en'
                && $subject === 'Project update: '.$mail->ticket->ticket_code
                && str_contains($html, 'Project update')
                && str_contains($html, 'Stage completed:')
                && ! str_contains($html, 'Actualización del proyecto');
        });
    }

    public function test_stage_update_route_cannot_advance_the_ticket(): void
    {
        $ticket = $this->createTicket()->fresh(['service.stages', 'currentStage']);
        $nextStage = $ticket->service->stages()->orderBy('sort_order')->skip(1)->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('admin.tickets.stage.update', $ticket), [
                'service_stage_id' => $nextStage->id,
                'notes' => 'Trying to jump ahead.',
            ])
            ->assertUnprocessable();

        $this->assertSame($ticket->current_service_stage_id, $ticket->fresh()->current_service_stage_id);

        try {
            app(TicketLifecycleService::class)->moveToStage($ticket->fresh(['service']), $nextStage, $this->admin);
            $this->fail('Forward stage movement through the domain service should be rejected.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }
    }

    public function test_completing_current_stage_advances_once_and_duplicate_completion_does_not_skip(): void
    {
        $ticket = $this->createTicket()->fresh(['service.stages', 'stageEvents.serviceStage']);
        $orderedStages = $ticket->service->stages()->orderBy('sort_order')->take(3)->get();
        $firstEvent = $this->eventForStage($ticket, $orderedStages[0]->id);

        $this->actingAs($this->admin)
            ->put(route('admin.tickets.stages.complete', [$ticket, $firstEvent]), [
                'stage_event_id' => $firstEvent->id,
                'notes' => 'Done.',
            ])
            ->assertRedirect(route('admin.tickets.show', $ticket));

        $ticket->refresh();
        $this->assertSame($orderedStages[1]->id, $ticket->current_service_stage_id);
        $this->assertSame(StageEventStatus::COMPLETED, $firstEvent->fresh()->status);
        $this->assertSame(StageEventStatus::CURRENT, $this->eventForStage($ticket, $orderedStages[1]->id)->status);

        $this->actingAs($this->admin)
            ->put(route('admin.tickets.stages.complete', [$ticket, $firstEvent]), [
                'stage_event_id' => $firstEvent->id,
                'notes' => 'Duplicate click.',
            ])
            ->assertRedirect(route('admin.tickets.show', $ticket));

        $this->assertSame($orderedStages[1]->id, $ticket->fresh()->current_service_stage_id);
        $this->assertSame(StageEventStatus::PENDING, $this->eventForStage($ticket, $orderedStages[2]->id)->status);
    }

    public function test_future_stage_completion_is_rejected_and_final_stage_completion_closes_ticket(): void
    {
        $ticket = $this->createTicket()->fresh(['service.stages', 'stageEvents.serviceStage']);
        $orderedStages = $ticket->service->stages()->orderBy('sort_order')->get()->values();
        $futureEvent = $this->eventForStage($ticket, $orderedStages->get(1)->id);

        $this->actingAs($this->admin)
            ->put(route('admin.tickets.stages.complete', [$ticket, $futureEvent]), [
                'stage_event_id' => $futureEvent->id,
            ])
            ->assertUnprocessable();

        foreach ($orderedStages as $stage) {
            $ticket->refresh();
            $event = $this->eventForStage($ticket, $ticket->current_service_stage_id);

            $this->actingAs($this->admin)
                ->put(route('admin.tickets.stages.complete', [$ticket, $event]), [
                    'stage_event_id' => $event->id,
                    'notes' => 'Complete current.',
                ])
                ->assertRedirect(route('admin.tickets.show', $ticket));
        }

        $this->assertSame(TicketStatus::COMPLETED, $ticket->fresh()->status);
        $this->assertSame($orderedStages->last()->id, $ticket->fresh()->current_service_stage_id);
    }

    public function test_reopening_restores_current_stage_without_note_based_completion_history_or_duplicate_reopens(): void
    {
        $ticket = $this->createTicket()->fresh(['stageEvents.serviceStage']);
        $event = $this->eventForStage($ticket, $ticket->current_service_stage_id);

        $this->actingAs($this->admin)
            ->put(route('admin.tickets.stages.complete', [$ticket, $event]), [
                'stage_event_id' => $event->id,
            ])
            ->assertRedirect(route('admin.tickets.show', $ticket));

        $completedAt = $event->fresh()->completed_at;
        $this->assertNotNull($completedAt);

        $this->travel(1)->minute();

        $this->actingAs($this->admin)
            ->put(route('admin.tickets.stages.reopen', [$ticket, $event]), [
                'stage_event_id' => $event->id,
                'notes' => 'Needs one more check.',
            ])
            ->assertRedirect(route('admin.tickets.show', $ticket));

        $event->refresh();
        $this->assertSame(StageEventStatus::CURRENT, $event->status);
        $this->assertNull($event->completed_at);
        $this->assertSame($event->service_stage_id, $ticket->fresh()->current_service_stage_id);
        $this->assertStringNotContainsString($completedAt->format('Y-m-d H:i'), (string) $event->notes);

        $notesAfterFirstReopen = $event->notes;

        $this->actingAs($this->admin)
            ->put(route('admin.tickets.stages.reopen', [$ticket, $event]), [
                'stage_event_id' => $event->id,
                'notes' => 'Duplicate reopen click.',
            ])
            ->assertRedirect(route('admin.tickets.show', $ticket));

        $this->assertSame($notesAfterFirstReopen, $event->fresh()->notes);
        $this->assertSame(1, $ticket->stageEvents()->where('status', StageEventStatus::CURRENT)->count());
    }

    public function test_stage_rollback_is_strictly_sequential_and_supersedes_abandoned_client_messages(): void
    {
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        $ticket = $this->createTicket('en', 'rollback.client@example.com', 'Rollback integrity')->fresh(['service.stages', 'stageEvents.serviceStage']);
        $ticket->forceFill(['client_user_id' => $client->id])->save();
        $orderedStages = $ticket->service->stages()->orderBy('sort_order')->take(3)->get()->values();
        $firstEvent = $this->eventForStage($ticket, $orderedStages[0]->id);
        $secondEvent = $this->eventForStage($ticket, $orderedStages[1]->id);
        $thirdEvent = $this->eventForStage($ticket, $orderedStages[2]->id);

        $this->actingAs($this->admin)
            ->put(route('admin.tickets.stages.complete', [$ticket, $firstEvent]), [
                'stage_event_id' => $firstEvent->id,
                'notes' => 'Stage one complete.',
            ])
            ->assertRedirect(route('admin.tickets.show', $ticket));

        $this->actingAs($this->admin)
            ->put(route('admin.tickets.stages.complete', [$ticket, $secondEvent]), [
                'stage_event_id' => $secondEvent->id,
                'notes' => 'Stage two complete.',
            ])
            ->assertRedirect(route('admin.tickets.show', $ticket));

        $thirdEvent->refresh()->forceFill([
            'notes' => 'Abandoned execution message visible only until rollback.',
        ])->save();

        $this->assertSame($orderedStages[2]->id, $ticket->fresh()->current_service_stage_id);

        $this->actingAs($this->admin)
            ->put(route('admin.tickets.stages.reopen', [$ticket, $firstEvent]), [
                'stage_event_id' => $firstEvent->id,
                'notes' => 'Trying to skip stage two.',
            ])
            ->assertUnprocessable();

        $this->actingAs($this->admin)
            ->put(route('admin.tickets.stages.reopen', [$ticket, $secondEvent]), [
                'stage_event_id' => $secondEvent->id,
                'notes' => 'Rollback one step for correction.',
            ])
            ->assertRedirect(route('admin.tickets.show', $ticket));

        $ticket->refresh();
        $firstEvent->refresh();
        $secondEvent->refresh();
        $thirdEvent->refresh();

        $this->assertSame($orderedStages[1]->id, $ticket->current_service_stage_id);
        $this->assertSame(StageEventStatus::COMPLETED, $firstEvent->status);
        $this->assertSame(StageEventStatus::CURRENT, $secondEvent->status);
        $this->assertSame(StageEventStatus::PENDING, $thirdEvent->status);
        $this->assertNull($secondEvent->completed_at);
        $this->assertNull($thirdEvent->entered_at);
        $this->assertNotNull($thirdEvent->superseded_at);
        $this->assertSame(2, $secondEvent->attempt_number);
        $this->assertSame(1, $ticket->stageEvents()->where('status', StageEventStatus::CURRENT)->count());
        $this->assertDatabaseHas('ticket_stage_audits', [
            'ticket_id' => $ticket->id,
            'action' => 'rolled_back_from',
            'rollback_from_stage_id' => $thirdEvent->service_stage_id,
            'rollback_to_stage_id' => $secondEvent->service_stage_id,
            'actor_user_id' => $this->admin->id,
        ]);
        $this->assertGreaterThanOrEqual(2, TicketStageAudit::query()->where('ticket_id', $ticket->id)->count());

        $this->actingAs($client)
            ->get(route('client.tickets.show', $ticket))
            ->assertOk()
            ->assertDontSee('Abandoned execution message visible only until rollback.');

        $this->post(route('tracking.show'), [
            'ticket_code' => $ticket->ticket_code,
            'email' => $ticket->email,
        ])
            ->assertOk()
            ->assertDontSee('Abandoned execution message visible only until rollback.');

        $this->actingAs($this->admin)
            ->get(route('admin.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Abandoned execution message visible only until rollback.')
            ->assertSee(__('site.previous_execution_archived'))
            ->assertSee(__('site.stage_audit_history'));

        $this->actingAs($this->admin)
            ->put(route('admin.tickets.stages.reopen', [$ticket, $firstEvent]), [
                'stage_event_id' => $firstEvent->id,
                'notes' => 'Rollback another single step.',
            ])
            ->assertRedirect(route('admin.tickets.show', $ticket));

        $this->assertSame($orderedStages[0]->id, $ticket->fresh()->current_service_stage_id);
        $this->assertSame(StageEventStatus::CURRENT, $firstEvent->fresh()->status);
        $this->assertSame(StageEventStatus::PENDING, $secondEvent->fresh()->status);
        $this->assertSame(1, $ticket->stageEvents()->where('status', StageEventStatus::CURRENT)->count());
    }

    public function test_stage_ui_exposes_only_current_completion_and_immediate_previous_reopen(): void
    {
        $ticket = $this->createTicket('en', 'stage-ui@example.com', 'Stage UI')->fresh(['service.stages', 'stageEvents.serviceStage']);
        $orderedStages = $ticket->service->stages()->orderBy('sort_order')->take(3)->get()->values();
        $firstEvent = $this->eventForStage($ticket, $orderedStages[0]->id);
        $secondEvent = $this->eventForStage($ticket, $orderedStages[1]->id);

        $this->actingAs($this->admin)
            ->get(route('admin.tickets.show', $ticket))
            ->assertOk()
            ->assertSee(__('site.mark_stage_completed'))
            ->assertDontSee(__('site.reopen_previous_stage'));

        $this->actingAs($this->admin)
            ->put(route('admin.tickets.stages.complete', [$ticket, $firstEvent]), [
                'stage_event_id' => $firstEvent->id,
            ])
            ->assertRedirect(route('admin.tickets.show', $ticket));
        $this->actingAs($this->admin)
            ->put(route('admin.tickets.stages.complete', [$ticket, $secondEvent]), [
                'stage_event_id' => $secondEvent->id,
            ])
            ->assertRedirect(route('admin.tickets.show', $ticket));

        $this->actingAs($this->admin)
            ->get(route('admin.tickets.show', $ticket))
            ->assertOk()
            ->assertSee(__('site.reopen_previous_stage'))
            ->assertSee(__('site.stage_cannot_reopen_until_previous'));
    }

    public function test_demo_seeder_advances_ticket_stages_without_operational_mail(): void
    {
        Mail::fake();

        $this->seed(DemoDataSeeder::class);
        $this->seed(DemoDataSeeder::class);

        Mail::assertNothingSent();

        $ticket = Ticket::query()
            ->where('project_name', 'Portal de seguimiento comercial')
            ->with(['currentStage', 'stageEvents'])
            ->firstOrFail();

        $this->assertSame('STR', $ticket->currentStage?->code);
        $this->assertSame(1, $ticket->stageEvents->where('status', StageEventStatus::CURRENT)->count());
    }

    public function test_ticket_file_visibility_toggles_are_immediate_for_the_authorized_client(): void
    {
        Storage::fake('local');
        $ticket = $this->createTicket();
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        $ticket->forceFill(['client_user_id' => $client->id])->save();
        $file = $this->createStoredFile($ticket, false);

        $this->actingAs($client)
            ->get(route('client.tickets.show', $ticket))
            ->assertOk()
            ->assertDontSee($file->title);

        $this->actingAs($this->admin)
            ->put(route('admin.tickets.files.visibility.update', [$ticket, $file]))
            ->assertRedirect(route('admin.tickets.show', $ticket));

        $this->actingAs($client)
            ->get(route('client.tickets.show', $ticket))
            ->assertOk()
            ->assertSee($file->title);

        $this->actingAs($this->admin)
            ->put(route('admin.tickets.files.visibility.update', [$ticket, $file]))
            ->assertRedirect(route('admin.tickets.show', $ticket));

        $this->actingAs($client)
            ->get(route('client.tickets.show', $ticket))
            ->assertOk()
            ->assertDontSee($file->title);
    }

    public function test_ticket_file_download_authorization_covers_client_tracking_and_missing_storage(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['role' => UserRole::CLIENT]);
        $intruder = User::factory()->create(['role' => UserRole::CLIENT]);
        $ticket = $this->createTicket();
        $ticket->forceFill(['client_user_id' => $owner->id])->save();
        $visibleFile = $this->createStoredFile($ticket, true, 'Visible package');
        $hiddenFile = $this->createStoredFile($ticket, false, 'Hidden package');

        $this->actingAs($owner)
            ->get(route('client.tickets.files.download', [$ticket, $hiddenFile]))
            ->assertNotFound();

        $this->actingAs($intruder)
            ->get(route('client.tickets.files.download', [$ticket, $visibleFile]))
            ->assertNotFound();

        $this->get(route('tracking.files.download', [$ticket, $visibleFile]))
            ->assertForbidden();

        $wrongHashUrl = URL::temporarySignedRoute('tracking.files.download', now()->addMinutes(5), [
            'ticket' => $ticket,
            'file' => $visibleFile,
            'email_hash' => hash('sha256', 'other@example.com'),
        ]);
        $this->get($wrongHashUrl)->assertNotFound();

        $validUrl = URL::temporarySignedRoute('tracking.files.download', now()->addMinutes(5), [
            'ticket' => $ticket,
            'file' => $visibleFile,
            'email_hash' => hash('sha256', strtolower($ticket->email)),
        ]);
        $this->get($validUrl)->assertOk()->assertHeader('content-disposition');

        Storage::disk('local')->delete($visibleFile->storage_path);
        $this->actingAs($owner)
            ->get(route('client.tickets.files.download', [$ticket, $visibleFile]))
            ->assertNotFound();
    }

    public function test_linked_deliverable_visibility_blocks_hidden_and_cross_ticket_files_for_clients_only(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['role' => UserRole::CLIENT]);
        $ticket = $this->createTicket();
        $otherTicket = $this->createTicket('es', 'other.deliverable@example.com', 'Other deliverable owner');
        $ticket->forceFill(['client_user_id' => $owner->id])->save();
        app(TicketLifecycleService::class)->ensureDeliverables($ticket);
        app(TicketLifecycleService::class)->ensureDeliverables($otherTicket);

        $visibleDeliverable = $ticket->deliverables()->whereHas('serviceDeliverable')->firstOrFail();
        $hiddenDeliverable = $ticket->deliverables()
            ->whereHas('serviceDeliverable')
            ->whereKeyNot($visibleDeliverable->id)
            ->first();

        if (! $hiddenDeliverable) {
            $hiddenTemplate = $ticket->service->deliverables()->create([
                'name' => 'Internal validation packet',
                'description' => 'Hidden deliverable template for authorization coverage.',
                'sort_order' => 999,
                'is_active' => true,
                'is_client_visible_by_default' => false,
            ]);

            $hiddenDeliverable = $ticket->deliverables()->create([
                'service_deliverable_id' => $hiddenTemplate->id,
                'name' => $hiddenTemplate->name,
                'description' => $hiddenTemplate->description,
                'status' => 'pending',
                'sort_order' => $hiddenTemplate->sort_order,
            ]);
        }

        $hiddenDeliverable->serviceDeliverable()->firstOrFail()
            ->forceFill(['is_client_visible_by_default' => false])
            ->save();
        $otherDeliverable = $otherTicket->deliverables()->firstOrFail();

        $visibleLinked = $this->createStoredFile($ticket, true, 'Visible linked deliverable', $visibleDeliverable->id);
        $hiddenLinked = $this->createStoredFile($ticket, true, 'Hidden linked deliverable', $hiddenDeliverable->id);
        $crossTicketLinked = $this->createStoredFile($ticket, true, 'Cross ticket linked deliverable', $otherDeliverable->id);

        $this->actingAs($owner)
            ->get(route('client.tickets.show', $ticket))
            ->assertOk()
            ->assertSee($visibleLinked->title)
            ->assertDontSee($hiddenLinked->title)
            ->assertDontSee($crossTicketLinked->title);

        $validHiddenUrl = URL::temporarySignedRoute('tracking.files.download', now()->addMinutes(5), [
            'ticket' => $ticket,
            'file' => $hiddenLinked,
            'email_hash' => hash('sha256', strtolower($ticket->email)),
        ]);
        $this->get($validHiddenUrl)->assertNotFound();

        $this->actingAs($owner)
            ->get(route('client.tickets.files.download', [$ticket, $crossTicketLinked]))
            ->assertNotFound();

        $this->actingAs($this->admin)
            ->get(route('admin.tickets.files.download', [$ticket, $hiddenLinked]))
            ->assertOk();
    }

    private function createTicket(string $language = 'es', string $email = 'workflow.client@example.com', string $projectName = 'Workflow integrity'): Ticket
    {
        $service = Service::query()->firstOrFail();

        $this->post(route('requests.store'), [
            'first_name' => 'Workflow',
            'last_name' => 'Client',
            'email' => $email,
            'phone' => '+57 300 123 4567',
            'project_name' => $projectName,
            'project_location' => 'Bogota',
            'preferred_language' => $language,
            'service_id' => $service->id,
            'project_description' => 'A scoped request for ticket workflow integrity validation.',
            'target_date' => now()->addWeeks(2)->toDateString(),
        ])->assertRedirect(route('tracking.index'));

        return Ticket::query()
            ->where('email', $email)
            ->where('project_name', $projectName)
            ->latest('id')
            ->firstOrFail();
    }

    private function eventForStage(Ticket $ticket, int $stageId): TicketStageEvent
    {
        return TicketStageEvent::query()
            ->where('ticket_id', $ticket->id)
            ->where('service_stage_id', $stageId)
            ->firstOrFail();
    }

    private function createStoredFile(Ticket $ticket, bool $visible, string $title = 'Workflow package', ?int $ticketDeliverableId = null): TicketFile
    {
        $path = UploadedFile::fake()->create(str($title)->slug().'.txt', 12, 'text/plain')
            ->storeAs("tests/tickets/{$ticket->ticket_code}", str($title)->slug().'.txt', 'local');

        return $ticket->files()->create([
            'title' => $title,
            'ticket_deliverable_id' => $ticketDeliverableId,
            'original_name' => basename($path),
            'stored_name' => basename($path),
            'mime_type' => 'text/plain',
            'size_bytes' => Storage::disk('local')->size($path),
            'storage_provider' => 'local_stub',
            'storage_disk' => 'local',
            'storage_path' => $path,
            'visibility' => $visible ? 'client' : 'internal',
            'delivery_type' => 'internal',
            'is_client_visible' => $visible,
            'uploaded_at' => now(),
        ]);
    }
}
