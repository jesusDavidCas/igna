<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkProjectDeletionAllMatchingPreviewRequest;
use App\Http\Requests\Admin\BulkProjectDeletionConfirmRequest;
use App\Http\Requests\Admin\BulkProjectDeletionPreviewRequest;
use App\Http\Requests\Admin\GuardedDeletionRequest;
use App\Http\Requests\Admin\TicketClientAssignmentRequest;
use App\Http\Requests\Admin\TicketDocumentReviewRequest;
use App\Http\Requests\Admin\TicketFileUploadRequest;
use App\Http\Requests\Admin\TicketStageUpdateRequest;
use App\Models\Ticket;
use App\Models\TicketFile;
use App\Models\TicketStageEvent;
use App\Models\User;
use App\Services\Deletion\DeleteProject;
use App\Services\Files\GoogleDriveFileManager;
use App\Services\Notifications\ProjectNotificationService;
use App\Services\Tickets\TicketLifecycleService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class TicketController extends Controller
{
    private const ALL_MATCHING_CONFIRMATION_PHRASE = 'DELETE ALL PROJECTS';

    public function index(Request $request, DeleteProject $deleteProject): View
    {
        $sort = $request->query('sort') === 'created_at' ? 'created_at' : 'created_at';
        $direction = in_array($request->query('direction'), ['asc', 'desc'], true)
            ? $request->query('direction')
            : 'desc';

        $perPage = in_array((int) $request->query('per_page', 15), [15, 30, 50], true)
            ? (int) $request->query('per_page', 15)
            : 15;
        $tickets = $this->ticketIndexQuery($sort, $direction)
            ->with(['service', 'currentStage'])
            ->withCount(['files', 'deliverables', 'stageEvents', 'stageAudits'])
            ->paginate($perPage)
            ->withQueryString();
        $bulkRelatedTicketIds = $request->user()?->isSuperAdmin()
            ? $tickets->getCollection()
                ->filter(fn (Ticket $ticket): bool => $deleteProject->hasRelatedInformation($ticket))
                ->pluck('id')
                ->flip()
            : collect();
        $allMatchingTicketIds = $request->user()?->isSuperAdmin()
            ? $this->ticketIndexQuery($sort, $direction)->pluck('id')->map(fn (mixed $id): int => (int) $id)->all()
            : [];

        return view('admin.tickets.index', [
            'tickets' => $tickets,
            'bulkRelatedTicketIds' => $bulkRelatedTicketIds,
            'selectionContext' => $request->user()?->isSuperAdmin()
                ? $this->storeSelectionContext($request, 'visible', $tickets->getCollection()->pluck('id')->map(fn (mixed $id): int => (int) $id)->all())
                : null,
            'allMatchingSelectionContext' => $request->user()?->isSuperAdmin() && $allMatchingTicketIds !== []
                ? $this->storeSelectionContext($request, 'all_matching', $allMatchingTicketIds)
                : null,
            'allMatchingCount' => count($allMatchingTicketIds),
            'sort' => $sort,
            'direction' => $direction,
            'perPage' => $perPage,
        ]);
    }

    public function bulkDeletePreview(
        BulkProjectDeletionPreviewRequest $request,
        DeleteProject $deleteProject,
    ): View {
        $ids = collect($request->validated('ticket_ids'))->map(fn (mixed $id): int => (int) $id)->values();

        return $this->renderBulkDeletePreview($request, $deleteProject, $ids, false);
    }

    public function bulkDeleteAllMatchingPreview(
        BulkProjectDeletionAllMatchingPreviewRequest $request,
        DeleteProject $deleteProject,
    ): View {
        return $this->renderBulkDeletePreview($request, $deleteProject, collect($request->ticketIds()), true);
    }

    private function renderBulkDeletePreview(
        Request $request,
        DeleteProject $deleteProject,
        Collection $ids,
        bool $isAllMatching,
    ): View {
        $tickets = $this->bulkTickets($ids);

        if ($tickets->count() !== $ids->count()) {
            throw ValidationException::withMessages(['ticket_ids' => __('site.bulk_delete_invalid_selection')]);
        }

        $confirmationToken = Str::random(40);
        session()->put('bulk_ticket_delete.confirmations.'.$confirmationToken, [
            'user_id' => $request->user()->id,
            'expires_at' => now()->addMinutes(10)->getTimestamp(),
            'scope' => $isAllMatching ? 'all_matching' : 'visible',
            'tickets' => $tickets->mapWithKeys(fn (Ticket $ticket): array => [
                $ticket->id => [
                    'ticket_code' => $ticket->ticket_code,
                    'fingerprint' => $deleteProject->fingerprint($ticket),
                ],
            ])->all(),
        ]);

        $impacts = $tickets->mapWithKeys(fn (Ticket $ticket): array => [$ticket->id => $deleteProject->impact($ticket)]);

        return view('admin.tickets.bulk-delete-preview', [
            'tickets' => $ids->map(fn (int $id): Ticket => $tickets->get($id)),
            'impacts' => $impacts,
            'totalImpactCounts' => collect($impacts)->map(fn ($impact) => $impact->counts())
                ->reduce(function (array $totals, array $counts): array {
                    foreach ($counts as $key => $count) {
                        $totals[$key] = ($totals[$key] ?? 0) + $count;
                    }

                    return $totals;
                }, []),
            'isAllMatching' => $isAllMatching,
            'allMatchingConfirmationPhrase' => self::ALL_MATCHING_CONFIRMATION_PHRASE,
            'confirmationToken' => $confirmationToken,
        ]);
    }

    public function bulkDestroy(
        BulkProjectDeletionConfirmRequest $request,
        DeleteProject $deleteProject,
    ): RedirectResponse {
        $token = $request->validated('confirmation_token');
        $confirmation = session()->get('bulk_ticket_delete.confirmations.'.$token);

        if (! is_array($confirmation)
            || ($confirmation['user_id'] ?? null) !== $request->user()->id
            || ($confirmation['expires_at'] ?? 0) < now()->getTimestamp()
            || ! in_array($confirmation['scope'] ?? null, ['visible', 'all_matching'], true)
            || ! is_array($confirmation['tickets'] ?? null)) {
            throw ValidationException::withMessages(['confirmation_token' => __('site.bulk_delete_confirmation_expired')]);
        }

        if (($confirmation['scope'] ?? null) === 'all_matching'
            && $request->validated('typed_confirmation') !== self::ALL_MATCHING_CONFIRMATION_PHRASE) {
            throw ValidationException::withMessages(['typed_confirmation' => __('site.bulk_delete_all_matching_phrase_invalid')]);
        }

        $ids = collect(array_keys($confirmation['tickets']))->map(fn (mixed $id): int => (int) $id)->values();
        $tickets = $this->bulkTickets($ids);

        // Consume before side effects so the confirmation cannot be replayed.
        session()->forget('bulk_ticket_delete.confirmations.'.$token);

        $deletedCodes = [];
        $skippedCodes = [];

        foreach ($ids as $id) {
            $ticket = $tickets->get($id);
            $snapshot = $confirmation['tickets'][$id] ?? null;

            if (! is_array($snapshot)
                || ! is_string($snapshot['fingerprint'] ?? null)
                || ! $ticket
                || ! hash_equals($snapshot['fingerprint'], $deleteProject->fingerprint($ticket))) {
                $skippedCodes[] = is_array($snapshot) ? ($snapshot['ticket_code'] ?? "#{$id}") : "#{$id}";

                continue;
            }

            try {
                if ($deleteProject->delete($ticket, $request->user(), $snapshot['fingerprint'])) {
                    $deletedCodes[] = $ticket->ticket_code;
                } else {
                    $skippedCodes[] = $ticket->ticket_code;
                }
            } catch (Throwable $exception) {
                report($exception);
                $skippedCodes[] = $ticket->ticket_code;
            }
        }

        $response = redirect()->route('admin.tickets.index');

        if ($deletedCodes !== []) {
            $response->with('success', trans_choice('site.bulk_delete_success', count($deletedCodes), ['count' => count($deletedCodes)]));
        }

        return $skippedCodes === []
            ? $response
            : $response->withErrors(['bulk_delete' => __('site.bulk_delete_partial_failure', [
                'count' => count($skippedCodes),
                'projects' => implode(', ', $skippedCodes),
            ])]);
    }

    public function show(Ticket $ticket, DeleteProject $deleteProject): View
    {
        app(TicketLifecycleService::class)->ensureDeliverables($ticket);

        $ticket->load([
            'client',
            'proposal',
            'service.stages' => fn ($query) => $query->orderBy('sort_order'),
            'currentStage',
            'stageEvents.serviceStage',
            'stageEvents.audits.actor',
            'files.uploadedBy',
            'files.firstAdminDownloadedBy',
            'files.reviewedBy',
            'files.rejectedBy',
            'deliverables.files.uploadedBy',
            'deliverables.files.firstAdminDownloadedBy',
            'deliverables.files.reviewedBy',
            'deliverables.files.rejectedBy',
        ]);

        $orderedStages = $ticket->service?->stages?->values() ?? collect();
        $currentIndex = $orderedStages->search(fn ($stage): bool => $stage->id === $ticket->current_service_stage_id);

        return view('admin.tickets.show', [
            'ticket' => $ticket,
            'nextStage' => $currentIndex === false ? null : $orderedStages->get($currentIndex + 1),
            'previousStage' => $currentIndex === false ? null : $orderedStages->get($currentIndex - 1),
            'clients' => User::query()
                ->where('role', UserRole::CLIENT)
                ->where('is_active', true)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(),
            'deletionImpact' => $deleteProject->impact($ticket),
        ]);
    }

    public function destroy(GuardedDeletionRequest $request, Ticket $ticket, DeleteProject $deleteProject): RedirectResponse
    {
        $deleteProject->delete($ticket, $request->user());

        return redirect()->route('admin.tickets.index')->with('success', __('site.project_deleted'));
    }

    public function moveBack(TicketStageUpdateRequest $request, Ticket $ticket, TicketLifecycleService $ticketLifecycleService): RedirectResponse
    {
        abort_unless($ticket->hasCatalogService(), 422);

        $previousStage = $ticket->service->stages()
            ->where('service_stages.id', $request->validated('service_stage_id'))
            ->firstOrFail();
        abort_unless($this->adjacentStage($ticket, -1)?->id === $previousStage->id, 422);
        $previousEvent = $ticket->stageEvents()
            ->where('service_stage_id', $previousStage->id)
            ->firstOrFail();

        $ticketLifecycleService->reopenStage(
            $ticket,
            $previousEvent,
            $request->user(),
            $request->validated('notes'),
        );

        return redirect()->route('admin.tickets.show', $ticket)->with('success', __('site.ticket_stage_moved_back'));
    }

    public function updateClient(TicketClientAssignmentRequest $request, Ticket $ticket): RedirectResponse
    {
        $clientId = $request->validated('client_user_id');

        if ($clientId) {
            $client = User::query()
                ->where('role', UserRole::CLIENT)
                ->whereKey($clientId)
                ->firstOrFail();

            $ticket->forceFill(['client_user_id' => $client->id])->save();
        } else {
            $ticket->forceFill(['client_user_id' => null])->save();
        }

        return redirect()->route('admin.tickets.show', $ticket)->with('success', __('site.ticket_client_updated'));
    }

    public function updateStage(
        TicketStageUpdateRequest $request,
        Ticket $ticket,
        TicketLifecycleService $ticketLifecycleService,
    ): RedirectResponse {
        abort_unless($ticket->hasCatalogService(), 422);

        $stage = $ticket->service->stages()
            ->where('service_stages.id', $request->validated('service_stage_id'))
            ->firstOrFail();
        abort_unless($stage->id === $ticket->current_service_stage_id, 422);

        return redirect()->route('admin.tickets.show', $ticket)->with('success', __('site.ticket_stage_updated'));
    }

    private function adjacentStage(Ticket $ticket, int $offset): mixed
    {
        if (! $ticket->hasCatalogService()) {
            return null;
        }

        $orderedStages = $ticket->service->stages()->orderBy('sort_order')->get()->values();
        $currentIndex = $orderedStages->search(fn ($stage): bool => $stage->id === $ticket->current_service_stage_id);

        return $currentIndex === false ? null : $orderedStages->get($currentIndex + $offset);
    }

    public function completeStage(
        TicketStageUpdateRequest $request,
        Ticket $ticket,
        TicketStageEvent $event,
        TicketLifecycleService $ticketLifecycleService,
    ): RedirectResponse {
        $ticketLifecycleService->completeStage(
            $ticket,
            $event,
            $request->user(),
            $request->validated('notes'),
        );

        return redirect()->route('admin.tickets.show', $ticket)->with('success', __('site.ticket_stage_completed'));
    }

    public function reopenStage(
        TicketStageUpdateRequest $request,
        Ticket $ticket,
        TicketStageEvent $event,
        TicketLifecycleService $ticketLifecycleService,
    ): RedirectResponse {
        $ticketLifecycleService->reopenStage(
            $ticket,
            $event,
            $request->user(),
            $request->validated('notes'),
        );

        return redirect()->route('admin.tickets.show', $ticket)->with('success', __('site.ticket_stage_reopened'));
    }

    public function storeFile(
        TicketFileUploadRequest $request,
        Ticket $ticket,
        GoogleDriveFileManager $googleDriveFileManager,
        ProjectNotificationService $projectNotificationService,
    ): RedirectResponse {
        try {
            $storedFile = $googleDriveFileManager->storeTicketFile($ticket, $request->file('file'));
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput($request->safe()->except('file'))
                ->withErrors(['file' => __('site.file_upload_failed')]);
        }

        if ($request->validated('ticket_deliverable_id')) {
            abort_unless($ticket->deliverables()->whereKey($request->validated('ticket_deliverable_id'))->exists(), 404);
        }

        $file = TicketFile::query()->create([
            'ticket_id' => $ticket->id,
            'uploaded_by_user_id' => $request->user()->id,
            'ticket_deliverable_id' => $request->validated('ticket_deliverable_id'),
            'title' => $request->validated('title'),
            'original_name' => $request->file('file')->getClientOriginalName(),
            'deliverable_type' => $request->validated('deliverable_type'),
            'visibility' => $request->boolean('is_client_visible') ? 'client' : 'internal',
            'delivery_type' => $request->validated('delivery_type'),
            'upload_source' => 'admin',
            'review_status' => 'reviewed',
            'is_client_visible' => $request->boolean('is_client_visible'),
            'uploaded_at' => now(),
            ...$storedFile,
        ]);

        if ($file->deliverable) {
            $file->deliverable->update([
                'status' => $file->delivery_type === 'final' ? 'final_uploaded' : 'partial_uploaded',
            ]);
        }

        if ($file->is_client_visible) {
            $projectNotificationService->notifyTicket(
                $ticket,
                'file_available',
                'site.email_file_available_headline',
                messageKey: 'site.email_file_available_message',
                messageReplacements: ['file' => $file->title],
            );
        }

        return redirect()->route('admin.tickets.show', $ticket)->with('success', __('site.ticket_file_uploaded'));
    }

    public function updateFileVisibility(Ticket $ticket, TicketFile $file, ProjectNotificationService $projectNotificationService): RedirectResponse
    {
        abort_unless($file->ticket_id === $ticket->id, 404);

        $file->update([
            'is_client_visible' => ! $file->is_client_visible,
            'visibility' => ! $file->is_client_visible ? 'client' : 'internal',
        ]);

        if ($file->is_client_visible) {
            $projectNotificationService->notifyTicket(
                $ticket,
                'file_available',
                'site.email_file_available_headline',
                messageKey: 'site.email_file_available_message',
                messageReplacements: ['file' => $file->title],
            );
        }

        return redirect()->route('admin.tickets.show', $ticket)->with('success', __('site.file_visibility_updated'));
    }

    public function destroyFile(Ticket $ticket, TicketFile $file): RedirectResponse
    {
        abort_unless($file->ticket_id === $ticket->id, 404);

        if ($file->storage_disk && $file->storage_path) {
            Storage::disk($file->storage_disk)->delete($file->storage_path);
        }

        $file->delete();

        return redirect()->route('admin.tickets.show', $ticket)->with('success', __('site.file_deleted'));
    }

    public function markFileReviewed(TicketDocumentReviewRequest $request, Ticket $ticket, TicketFile $file): RedirectResponse
    {
        $this->assertReviewableClientDocument($ticket, $file);

        $file->forceFill([
            'review_status' => 'reviewed',
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
            'rejected_by_user_id' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ])->save();

        return redirect()->route('admin.tickets.show', $ticket)->with('success', __('site.document_marked_reviewed'));
    }

    public function rejectFile(TicketDocumentReviewRequest $request, Ticket $ticket, TicketFile $file): RedirectResponse
    {
        $this->assertReviewableClientDocument($ticket, $file);

        $file->forceFill([
            'review_status' => 'rejected',
            'rejected_by_user_id' => $request->user()->id,
            'rejected_at' => now(),
            'rejection_reason' => $request->validated('rejection_reason'),
            'reviewed_by_user_id' => null,
            'reviewed_at' => null,
        ])->save();

        return redirect()->route('admin.tickets.show', $ticket)->with('success', __('site.document_rejected'));
    }

    private function assertReviewableClientDocument(Ticket $ticket, TicketFile $file): void
    {
        abort_unless($file->ticket_id === $ticket->id, 404);
        abort_unless($file->isClientSubmitted(), 422);
    }

    /**
     * @param  array<int, int>  $ticketIds
     * @return Collection<int, Ticket>
     */
    private function bulkTickets($ticketIds)
    {
        return Ticket::query()
            ->with(['client', 'proposal', 'currentStage'])
            ->withCount(['files', 'deliverables', 'stageEvents', 'stageAudits'])
            ->whereKey($ticketIds)
            ->get()
            ->keyBy('id');
    }

    private function ticketIndexQuery(string $sort, string $direction)
    {
        return Ticket::query()
            ->orderBy($sort, $direction)
            ->orderBy('id', $direction);
    }

    /**
     * @param  array<int, int>  $ticketIds
     */
    private function storeSelectionContext(Request $request, string $scope, array $ticketIds): string
    {
        $contexts = collect(session()->get('bulk_ticket_delete.selection_contexts', []))
            ->filter(fn (mixed $context): bool => is_array($context) && ($context['expires_at'] ?? 0) >= now()->getTimestamp())
            ->all();
        $token = Str::random(40);
        $contexts[$token] = [
            'user_id' => $request->user()->id,
            'expires_at' => now()->addMinutes(10)->getTimestamp(),
            'scope' => $scope,
            'ticket_ids' => $ticketIds,
        ];

        session()->put('bulk_ticket_delete.selection_contexts', $contexts);

        return $token;
    }
}
