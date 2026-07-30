<?php

namespace Tests\Feature;

use App\Enums\StageEventStatus;
use App\Enums\UserRole;
use App\Events\TicketClientDocumentUploaded;
use App\Mail\TicketDocumentUploadedAdminMail;
use App\Mail\TicketDocumentUploadedClientMail;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\TicketFile;
use App\Models\User;
use App\Services\Notifications\ProjectNotificationService;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class TicketLayoutDocumentExchangeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('local');
        Storage::fake('public');
        Mail::fake();
        $this->seed();

        $this->admin = User::query()->where('role', UserRole::SUPER_ADMIN)->firstOrFail();
    }

    public function test_admin_ticket_layout_orders_sidebar_and_wide_deliverables_after_timeline(): void
    {
        $ticket = $this->createTicket()->fresh(['service.stages']);

        $this->actingAs($this->admin)
            ->get(route('admin.tickets.show', $ticket))
            ->assertOk()
            ->assertSeeInOrder([
                __('site.assign_client'),
                __('site.general_project_files'),
                __('site.stage_completion_control'),
            ])
            ->assertSeeInOrder([
                __('site.project_timeline'),
                'data-section="deliverables-wide"',
            ], false)
            ->assertSee('data-layout="deliverables-stacked"', false)
            ->assertSee('data-deliverable-section', false)
            ->assertSee(route('admin.tickets.files.store', $ticket), false)
            ->assertSee(__('site.delivery_type_final'));
    }

    public function test_deliverable_documents_render_as_full_width_rows_with_wrapping_actions(): void
    {
        $ticket = $this->createTicket()->fresh();
        app(\App\Services\Tickets\TicketLifecycleService::class)->ensureDeliverables($ticket);
        $deliverable = $ticket->deliverables()->firstOrFail();
        $longName = 'hydraulic-model-deliverable-with-a-very-long-original-filename-that-should-not-overflow-the-card-boundary.pdf';
        $path = UploadedFile::fake()->create($longName, 24, 'application/pdf')
            ->storeAs("tests/tickets/{$ticket->ticket_code}", $longName, 'local');

        $ticket->files()->create([
            'ticket_deliverable_id' => $deliverable->id,
            'title' => 'Long filename deliverable packet',
            'original_name' => $longName,
            'stored_name' => basename($path),
            'mime_type' => 'application/pdf',
            'size_bytes' => Storage::disk('local')->size($path),
            'storage_provider' => 'local_stub',
            'storage_disk' => 'local',
            'storage_path' => $path,
            'deliverable_type' => 'project_document',
            'visibility' => 'client',
            'delivery_type' => 'final',
            'upload_source' => 'admin',
            'review_status' => 'reviewed',
            'is_client_visible' => true,
            'uploaded_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('data-layout="deliverables-stacked"', false)
            ->assertSee('data-file-row', false)
            ->assertSee('data-file-actions', false)
            ->assertSee('data-file-card-info', false)
            ->assertSee('data-file-card-badges', false)
            ->assertSee(__('site.download_file'))
            ->assertSee(__('site.hide_from_client'))
            ->assertSee(__('site.delete'));
    }

    public function test_all_ticket_file_locations_use_shared_responsive_file_card_regions(): void
    {
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        $ticket = $this->createTicket('shared-cards@example.com', 'Shared cards');
        $ticket->forceFill(['client_user_id' => $client->id])->save();
        app(\App\Services\Tickets\TicketLifecycleService::class)->ensureDeliverables($ticket);
        $deliverable = $ticket->deliverables()->firstOrFail();
        $path = UploadedFile::fake()->create('shared-client-visible.pdf', 24, 'application/pdf')
            ->storeAs("tests/tickets/{$ticket->ticket_code}", 'shared-client-visible.pdf', 'local');

        $file = $ticket->files()->create([
            'ticket_deliverable_id' => $deliverable->id,
            'title' => 'Shared responsive card',
            'original_name' => 'shared-client-visible.pdf',
            'stored_name' => basename($path),
            'mime_type' => 'application/pdf',
            'size_bytes' => Storage::disk('local')->size($path),
            'storage_provider' => 'local_stub',
            'storage_disk' => 'local',
            'storage_path' => $path,
            'deliverable_type' => 'project_document',
            'visibility' => 'client',
            'delivery_type' => 'final',
            'upload_source' => 'admin',
            'review_status' => 'reviewed',
            'is_client_visible' => true,
            'uploaded_at' => now(),
        ]);

        $this->actingAs($client)
            ->post(route('client.tickets.documents.store', $ticket), [
                'category' => 'supporting_document',
                'document' => $this->validPdf('client-submitted-card.pdf'),
            ])
            ->assertRedirect();

        $adminHtml = $this->actingAs($this->admin)
            ->get(route('admin.tickets.show', $ticket))
            ->assertOk()
            ->assertSeeInOrder([
                'data-file-card-info',
                'data-file-card-badges',
                'data-file-card-actions',
            ], false)
            ->assertSee('ticket-file-card__reject-form', false)
            ->getContent();

        $this->assertStringContainsString(route('admin.tickets.files.download', [$ticket, $file]), $adminHtml);

        $this->actingAs($client)
            ->get(route('client.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('data-file-card', false)
            ->assertSee('data-file-card-info', false)
            ->assertSee('data-file-card-badges', false)
            ->assertSee('data-file-card-actions', false);

        $this->post(route('tracking.show'), [
            'ticket_code' => $ticket->ticket_code,
            'email' => $ticket->email,
        ])
            ->assertOk()
            ->assertSee('data-file-card', false)
            ->assertSee('data-file-card-info', false)
            ->assertSee('data-file-card-badges', false)
            ->assertSee('data-file-card-actions', false);
    }

    public function test_admin_general_proposal_file_uses_20_mb_policy_and_does_not_advance_stage(): void
    {
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        $ticket = $this->createTicket();
        $ticket->forceFill(['client_user_id' => $client->id])->save();
        $currentStageId = $ticket->current_service_stage_id;

        $this->actingAs($this->admin)
            ->post(route('admin.tickets.files.store', $ticket), [
                'title' => 'Proposal packet',
                'deliverable_type' => 'proposal',
                'delivery_type' => 'internal',
                'is_client_visible' => '1',
                'file' => UploadedFile::fake()->create('proposal.pdf', 19000, 'application/pdf'),
            ])
            ->assertRedirect(route('admin.tickets.show', $ticket));

        $file = TicketFile::query()->where('title', 'Proposal packet')->firstOrFail();

        $this->assertSame('admin', $file->upload_source);
        $this->assertSame('reviewed', $file->review_status);
        $this->assertSame('proposal', $file->deliverable_type);
        $this->assertTrue($file->is_client_visible);
        $this->assertSame($currentStageId, $ticket->fresh()->current_service_stage_id);

        $this->actingAs($client)
            ->get(route('client.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Proposal packet')
            ->assertSee(__('site.ticket_file_category_proposal'));

        $url = URL::temporarySignedRoute('tracking.files.download', now()->addMinutes(5), [
            'ticket' => $ticket,
            'file' => $file,
            'email_hash' => hash('sha256', strtolower($ticket->email)),
        ]);

        $this->get($url)
            ->assertOk()
            ->assertHeader('x-content-type-options', 'nosniff')
            ->assertHeader('content-disposition');
    }

    public function test_authenticated_client_uploads_valid_documents_and_keeps_them_pending_without_stage_change(): void
    {
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        $ticket = $this->createTicket();
        $ticket->forceFill(['client_user_id' => $client->id])->save();
        $currentStageId = $ticket->current_service_stage_id;

        foreach ([
            ['payment_receipt', $this->validPdf('receipt.pdf')],
            ['requested_document', UploadedFile::fake()->image('photo.jpg', 100, 80)],
            ['supporting_document', UploadedFile::fake()->image('diagram.png', 100, 80)],
        ] as [$category, $file]) {
            $this->actingAs($client)
                ->post(route('client.tickets.documents.store', $ticket), [
                'category' => $category,
                'document' => $file,
            ])
                ->assertRedirect()
                ->assertSessionHas('success', __('site.authenticated_document_received_successfully'));
        }

        $this->assertSame($currentStageId, $ticket->fresh()->current_service_stage_id);
        $this->assertSame(3, TicketFile::query()->where('ticket_id', $ticket->id)->count());
        $this->assertSame(3, TicketFile::query()->where('ticket_id', $ticket->id)->clientSubmitted()->where('review_status', 'pending_review')->count());
        Mail::assertSent(TicketDocumentUploadedClientMail::class, 3);
        Mail::assertSent(TicketDocumentUploadedAdminMail::class);

        $this->actingAs($client)
            ->get(route('client.tickets.show', $ticket))
            ->assertOk()
            ->assertSee(__('site.documents_you_sent'))
            ->assertSee(__('site.ticket_file_review_status_pending_review'));

        $file = TicketFile::query()->where('deliverable_type', 'payment_receipt')->firstOrFail();
        $this->assertNotSame('receipt.pdf', $file->stored_name);
        $this->assertSame('local', $file->storage_disk);
        $this->assertFalse(Storage::disk('public')->exists((string) $file->storage_path));
        Storage::disk('local')->assertExists($file->storage_path);

        $this->actingAs($client)
            ->get(route('client.tickets.files.download', [$ticket, $file]))
            ->assertOk()
            ->assertHeader('x-content-type-options', 'nosniff')
            ->assertHeader('content-disposition');
    }

    public function test_client_document_download_and_review_lifecycle_is_distinct_from_stage_and_payment_state(): void
    {
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        $ticket = $this->createTicket('review-lifecycle@example.com', 'Review lifecycle');
        $ticket->forceFill(['client_user_id' => $client->id])->save();
        $currentStageId = $ticket->current_service_stage_id;

        $this->actingAs($client)
            ->post(route('client.tickets.documents.store', $ticket), [
                'category' => 'payment_receipt',
                'document' => $this->validPdf('payment.pdf'),
            ])
            ->assertRedirect();

        $file = TicketFile::query()->where('ticket_id', $ticket->id)->clientSubmitted()->firstOrFail();
        $this->assertSame('pending_review', $file->review_status);

        $this->actingAs($this->admin)
            ->get(route('admin.tickets.show', $ticket))
            ->assertOk()
            ->assertSee(__('site.ticket_file_review_status_pending_review'));
        $this->assertSame('pending_review', $file->fresh()->review_status);

        $this->actingAs($client)
            ->get(route('client.tickets.files.download', [$ticket, $file]))
            ->assertOk();
        $this->assertSame('pending_review', $file->fresh()->review_status);

        $this->travel(1)->minute();
        $this->actingAs($this->admin)
            ->get(route('admin.tickets.files.download', [$ticket, $file]))
            ->assertOk()
            ->assertHeader('x-content-type-options', 'nosniff');

        $file->refresh();
        $firstDownloadAt = $file->first_admin_downloaded_at;
        $this->assertSame('downloaded', $file->review_status);
        $this->assertSame($this->admin->id, $file->first_admin_downloaded_by_user_id);
        $this->assertNotNull($firstDownloadAt);

        $this->travel(1)->minute();
        $this->actingAs($this->admin)
            ->get(route('admin.tickets.files.download', [$ticket, $file]))
            ->assertOk();
        $this->assertTrue($firstDownloadAt->equalTo($file->fresh()->first_admin_downloaded_at));

        $this->actingAs($client)
            ->get(route('client.tickets.show', $ticket))
            ->assertOk()
            ->assertSee(__('site.ticket_file_review_status_downloaded'))
            ->assertSee(__('site.ticket_file_downloaded_on', ['date' => $firstDownloadAt->format('Y-m-d H:i')]));

        $this->actingAs($this->admin)
            ->patch(route('admin.tickets.files.review.update', [$ticket, $file]))
            ->assertRedirect(route('admin.tickets.show', $ticket));

        $this->assertSame('reviewed', $file->fresh()->review_status);
        $this->assertSame($currentStageId, $ticket->fresh()->current_service_stage_id);

        $this->actingAs($this->admin)
            ->get(route('admin.tickets.files.download', [$ticket, $file]))
            ->assertOk();
        $this->assertSame('reviewed', $file->fresh()->review_status);

        $this->actingAs($client)
            ->post(route('client.tickets.documents.store', $ticket), [
                'category' => 'supporting_document',
                'document' => $this->validPdf('supporting.pdf'),
            ])
            ->assertRedirect();

        $rejectedFile = TicketFile::query()->where('deliverable_type', 'supporting_document')->firstOrFail();

        $this->actingAs($this->admin)
            ->patch(route('admin.tickets.files.reject.update', [$ticket, $rejectedFile]), [
                'rejection_reason' => '<b>Missing readable signature</b>',
            ])
            ->assertRedirect(route('admin.tickets.show', $ticket));

        $this->assertSame('rejected', $rejectedFile->fresh()->review_status);
        $this->assertSame('<b>Missing readable signature</b>', $rejectedFile->fresh()->rejection_reason);
        $this->assertSame($currentStageId, $ticket->fresh()->current_service_stage_id);

        $this->actingAs($client)
            ->get(route('client.tickets.show', $ticket))
            ->assertOk()
            ->assertSee(__('site.ticket_file_review_status_reviewed'))
            ->assertSee(__('site.ticket_file_review_status_rejected'))
            ->assertSee(e('<b>Missing readable signature</b>'), false);
    }

    public function test_admin_download_missing_storage_or_unrelated_files_do_not_change_review_state(): void
    {
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        $ticket = $this->createTicket('missing-review@example.com', 'Missing review');
        $ticket->forceFill(['client_user_id' => $client->id])->save();

        $this->actingAs($client)
            ->post(route('client.tickets.documents.store', $ticket), [
                'category' => 'requested_document',
                'document' => $this->validPdf('requested.pdf'),
            ])
            ->assertRedirect();

        $file = TicketFile::query()->clientSubmitted()->firstOrFail();
        Storage::disk('local')->delete($file->storage_path);

        $this->actingAs($this->admin)
            ->get(route('admin.tickets.files.download', [$ticket, $file]))
            ->assertNotFound();
        $this->assertSame('pending_review', $file->fresh()->review_status);

        $adminFile = $ticket->files()->create([
            'title' => 'Admin internal',
            'original_name' => 'admin.pdf',
            'stored_name' => 'admin.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 12,
            'storage_provider' => 'local_stub',
            'storage_disk' => 'local',
            'storage_path' => 'missing/admin.pdf',
            'deliverable_type' => 'proposal',
            'visibility' => 'internal',
            'delivery_type' => 'internal',
            'upload_source' => 'admin',
            'review_status' => 'reviewed',
            'is_client_visible' => false,
            'uploaded_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.tickets.files.reject.update', [$ticket, $adminFile]), [
                'rejection_reason' => 'Not a client submission',
            ])
            ->assertUnprocessable();
    }

    public function test_client_document_security_rejects_risky_or_mismatched_uploads_without_persistence(): void
    {
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        $ticket = $this->createTicket();
        $ticket->forceFill(['client_user_id' => $client->id])->save();

        $cases = [
            $this->validPdf('oversized.pdf', str_repeat('A', (2 * 1024 * 1024) + 1)),
            $this->rawUpload('document.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'PK'.str_repeat('A', 64)),
            $this->rawUpload('vector.svg', 'image/svg+xml', '<svg></svg>'),
            $this->rawUpload('page.pdf', 'text/html', '<html></html>'),
            $this->rawUpload('tool.exe', 'application/octet-stream', 'MZ'.str_repeat('A', 64)),
            $this->rawUpload('receipt.pdf.exe', 'application/octet-stream', 'MZ'.str_repeat('A', 64)),
            $this->rawUpload('image.jpg', 'image/jpeg', "%PDF-1.4\n%%EOF\n"),
            $this->rawUpload('broken.jpg', 'image/jpeg', 'not really an image'),
            $this->rawUpload('broken.pdf', 'application/pdf', '%PDF-1.4 without eof'),
        ];

        foreach ($cases as $file) {
            $this->actingAs($client)
                ->post(route('client.tickets.documents.store', $ticket), [
                    'category' => 'supporting_document',
                    'document' => $file,
                ])
                ->assertSessionHasErrors('document');
        }

        $this->assertDatabaseMissing('ticket_files', ['ticket_id' => $ticket->id]);
        Storage::disk('local')->assertMissing("client-documents/tickets/{$ticket->ticket_code}");
    }

    public function test_wrong_client_cannot_upload_or_download_another_clients_document(): void
    {
        $owner = User::factory()->create(['role' => UserRole::CLIENT]);
        $intruder = User::factory()->create(['role' => UserRole::CLIENT]);
        $ticket = $this->createTicket();
        $ticket->forceFill(['client_user_id' => $owner->id])->save();

        $this->actingAs($intruder)
            ->post(route('client.tickets.documents.store', $ticket), [
                'category' => 'requested_document',
                'document' => $this->validPdf('wrong.pdf'),
            ])
            ->assertNotFound();

        $this->actingAs($owner)
            ->post(route('client.tickets.documents.store', $ticket), [
                'category' => 'requested_document',
                'document' => $this->validPdf('owner.pdf'),
            ])
            ->assertRedirect();

        $file = TicketFile::query()->firstOrFail();

        $this->actingAs($intruder)
            ->get(route('client.tickets.files.download', [$ticket, $file]))
            ->assertNotFound();
    }

    public function test_public_tracking_signed_upload_succeeds_and_rejects_bad_contexts_or_rate_excess(): void
    {
        $ticket = $this->createTicket();
        $currentStageId = $ticket->current_service_stage_id;
        $emailHash = hash('sha256', strtolower($ticket->email));
        $signedUrl = URL::temporarySignedRoute('tracking.documents.store', now()->addMinutes(5), [
            'ticket' => $ticket,
            'email_hash' => $emailHash,
        ]);

        $this->post(route('tracking.documents.store', $ticket), [
            'category' => 'payment_receipt',
            'document' => $this->validPdf('unsigned.pdf'),
            'email_hash' => $emailHash,
        ])->assertForbidden();

        $expiredUrl = URL::temporarySignedRoute('tracking.documents.store', now()->subMinute(), [
            'ticket' => $ticket,
            'email_hash' => $emailHash,
        ]);
        $this->post($expiredUrl, [
            'category' => 'payment_receipt',
            'document' => $this->validPdf('expired.pdf'),
        ])->assertForbidden();

        $wrongHashUrl = URL::temporarySignedRoute('tracking.documents.store', now()->addMinutes(5), [
            'ticket' => $ticket,
            'email_hash' => hash('sha256', 'wrong@example.com'),
        ]);
        $this->post($wrongHashUrl, [
            'category' => 'payment_receipt',
            'document' => $this->validPdf('wrong-hash.pdf'),
        ])->assertNotFound();

        $this->withSession([
            'tracking_lookup' => [
                'ticket_code' => $ticket->ticket_code,
                'email' => $ticket->email,
            ],
        ])->post($signedUrl, [
            'category' => 'payment_receipt',
            'document' => $this->validPdf('public.pdf'),
        ])->assertRedirect(route('tracking.index'))
            ->assertSessionHas('success', __('site.tracking_document_received_successfully'))
            ->assertSessionMissing('tracking_lookup');

        $file = TicketFile::query()->where('upload_source', 'public_tracking')->firstOrFail();
        $this->assertSame('pending_review', $file->review_status);
        $this->assertSame($emailHash, $file->submitted_context_hash);
        $this->assertSame($currentStageId, $ticket->fresh()->current_service_stage_id);
        Mail::assertSent(TicketDocumentUploadedClientMail::class, fn (TicketDocumentUploadedClientMail $mail): bool => $mail->hasTo($ticket->email)
            && $mail->ticket->is($ticket)
            && $mail->file->is($file)
            && $mail->locale === 'en'
            && $mail->category === 'Payment receipt');
        Mail::assertSent(TicketDocumentUploadedAdminMail::class, fn (TicketDocumentUploadedAdminMail $mail): bool => $mail->ticket->is($ticket)
            && $mail->file->is($file)
            && $mail->hasTo('support@ignastudio.com'));
        Mail::assertNotSent(TicketDocumentUploadedAdminMail::class, fn (TicketDocumentUploadedAdminMail $mail): bool => $mail->hasTo($this->admin->email));

        $downloadUrl = URL::temporarySignedRoute('tracking.files.download', now()->addMinutes(5), [
            'ticket' => $ticket,
            'file' => $file,
            'email_hash' => $emailHash,
        ]);
        $this->get($downloadUrl)->assertOk()->assertHeader('x-content-type-options', 'nosniff');

        for ($i = 0; $i < 3; $i++) {
            $this->post($signedUrl, [
                'category' => 'supporting_document',
                'document' => $this->validPdf("rate-{$i}.pdf"),
            ])->assertRedirect(route('tracking.index'));
        }

        $this->post($signedUrl, [
            'category' => 'supporting_document',
            'document' => $this->validPdf('rate-limit.pdf'),
        ])->assertTooManyRequests();
    }

    public function test_initial_request_upload_policy_and_optional_flow(): void
    {
        $this->post(route('requests.store'), $this->requestPayload('no-file@example.com', 'No attachment'))
            ->assertRedirect(route('tracking.index'));

        $this->post(route('requests.store'), [
            ...$this->requestPayload('initial-pdf@example.com', 'Initial PDF'),
            'initial_file' => $this->validPdf('initial.pdf'),
        ])->assertRedirect(route('tracking.index'));

        $this->post(route('requests.store'), [
            ...$this->requestPayload('initial-image@example.com', 'Initial image'),
            'initial_file' => UploadedFile::fake()->image('initial.png', 100, 80),
        ])->assertRedirect(route('tracking.index'));

        $this->post(route('requests.store'), [
            ...$this->requestPayload('initial-large@example.com', 'Initial large'),
            'initial_file' => $this->validPdf('large.pdf', str_repeat('A', (2 * 1024 * 1024) + 1)),
        ])->assertSessionHasErrors('initial_file');

        $this->post(route('requests.store'), [
            ...$this->requestPayload('initial-docx@example.com', 'Initial DOCX'),
            'initial_file' => $this->rawUpload('initial.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'PK'.str_repeat('A', 64)),
        ])->assertSessionHasErrors('initial_file');

        $this->assertSame(2, TicketFile::query()->clientSubmitted()->where('upload_source', 'initial_request')->count());
    }

    public function test_client_upload_confirmation_uses_client_locale_and_active_deduplicated_admin_recipients(): void
    {
        $client = User::factory()->create([
            'role' => UserRole::CLIENT,
            'email' => 'portal.account@example.com',
            'preferred_language' => 'es',
        ]);
        $englishAdmin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'english.admin@example.com',
            'preferred_language' => 'en',
            'is_active' => true,
        ]);
        $inactiveAdmin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'inactive.admin@example.com',
            'preferred_language' => 'en',
            'is_active' => false,
        ]);
        $unrelatedAdmin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'unrelated.admin@example.com',
            'preferred_language' => 'en',
            'is_active' => true,
        ]);
        $ticket = $this->createTicket('ticket.contact@example.com', 'Portal client upload');
        $ticket->forceFill([
            'client_user_id' => $client->id,
            'preferred_language' => 'en',
        ])->save();
        $ticket->stageEvents()->firstOrFail()->forceFill([
            'changed_by_user_id' => $englishAdmin->id,
            'superseded_by_user_id' => $inactiveAdmin->id,
        ])->save();

        Mail::fake();

        $this->actingAs($client)
            ->post(route('client.tickets.documents.store', $ticket), [
                'category' => 'requested_document',
                'document' => $this->validPdf('requested.pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success', __('site.authenticated_document_received_successfully'));

        $file = TicketFile::query()->where('upload_source', 'authenticated_client')->firstOrFail();

        Mail::assertSent(TicketDocumentUploadedClientMail::class, fn (TicketDocumentUploadedClientMail $mail): bool => $mail->hasTo($client->email)
            && ! $mail->hasTo($ticket->email)
            && $mail->locale === 'es'
            && $mail->category === trans('site.ticket_file_category_requested_document', [], 'es'));
        Mail::assertSent(TicketDocumentUploadedAdminMail::class, fn (TicketDocumentUploadedAdminMail $mail): bool => $mail->hasTo($englishAdmin->email)
            && $mail->locale === 'en'
            && $mail->file->is($file));
        $this->assertSame(1, Mail::sent(TicketDocumentUploadedAdminMail::class, fn (TicketDocumentUploadedAdminMail $mail): bool => $mail->hasTo($englishAdmin->email))->count());
        Mail::assertNotSent(TicketDocumentUploadedAdminMail::class, fn (TicketDocumentUploadedAdminMail $mail): bool => $mail->hasTo($this->admin->email));
        Mail::assertNotSent(TicketDocumentUploadedAdminMail::class, fn (TicketDocumentUploadedAdminMail $mail): bool => $mail->hasTo($unrelatedAdmin->email));
        Mail::assertNotSent(TicketDocumentUploadedAdminMail::class, fn (TicketDocumentUploadedAdminMail $mail): bool => $mail->hasTo($inactiveAdmin->email));
    }

    public function test_document_upload_event_is_after_commit_and_mail_failure_does_not_remove_upload(): void
    {
        $this->assertInstanceOf(
            \Illuminate\Contracts\Events\ShouldDispatchAfterCommit::class,
            new TicketClientDocumentUploaded(1, 1, 'authenticated_client', 1),
        );

        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        $ticket = $this->createTicket('mail-failure@example.com', 'Mail failure upload');
        $ticket->forceFill(['client_user_id' => $client->id])->save();

        $notifications = \Mockery::mock(ProjectNotificationService::class);
        $notifications
            ->shouldReceive('notifyClientDocumentSubmitted')
            ->once()
            ->andThrow(new \RuntimeException('simulated mail failure'));
        $notifications->shouldReceive('notifyAdminsDocumentSubmitted')->never();
        $this->app->instance(ProjectNotificationService::class, $notifications);

        $this->actingAs($client)
            ->post(route('client.tickets.documents.store', $ticket), [
                'category' => 'supporting_document',
                'document' => $this->validPdf('mail-failure.pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success', __('site.authenticated_document_received_successfully'));

        $file = TicketFile::query()->where('ticket_id', $ticket->id)->clientSubmitted()->firstOrFail();

        $this->assertSame('pending_review', $file->review_status);
        Storage::disk('local')->assertExists($file->storage_path);
    }

    public function test_commercial_separation_and_demo_seed_regression(): void
    {
        $client = User::factory()->create(['role' => UserRole::CLIENT]);
        $ticket = $this->createTicket();
        $ticket->forceFill(['client_user_id' => $client->id])->save();
        $event = $ticket->stageEvents()->where('status', StageEventStatus::CURRENT)->firstOrFail();

        $this->actingAs($client)
            ->post(route('client.tickets.documents.store', $ticket), [
                'category' => 'payment_receipt',
                'document' => $this->validPdf('payment.pdf'),
            ])
            ->assertRedirect();

        $ticket->refresh();
        $this->assertNotSame('completed', $ticket->status->value);
        $this->assertSame(StageEventStatus::CURRENT, $event->fresh()->status);
        $this->assertSame($event->service_stage_id, $ticket->current_service_stage_id);

        Mail::fake();
        $this->seed(DemoDataSeeder::class);
        Mail::assertNothingSent();
    }

    private function createTicket(string $email = 'layout-docs@example.com', string $projectName = 'Layout docs'): Ticket
    {
        $this->post(route('requests.store'), $this->requestPayload($email, $projectName))
            ->assertRedirect(route('tracking.index'));

        return Ticket::query()
            ->where('email', $email)
            ->where('project_name', $projectName)
            ->latest('id')
            ->firstOrFail();
    }

    private function requestPayload(string $email, string $projectName): array
    {
        return [
            'first_name' => 'Layout',
            'last_name' => 'Client',
            'email' => $email,
            'phone' => '+57 300 123 4567',
            'project_name' => $projectName,
            'project_location' => 'Bogota',
            'preferred_language' => 'en',
            'service_id' => Service::query()->firstOrFail()->id,
            'project_description' => 'A request for secure document exchange validation.',
            'target_date' => now()->addWeeks(2)->toDateString(),
        ];
    }

    private function validPdf(string $name, string $extra = ''): UploadedFile
    {
        return $this->rawUpload($name, 'application/pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n{$extra}\n%%EOF\n");
    }

    private function rawUpload(string $name, string $mimeType, string $contents): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'igna-upload-');
        file_put_contents($path, $contents);

        return new UploadedFile($path, $name, $mimeType, null, true);
    }
}
