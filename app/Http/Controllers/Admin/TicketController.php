<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TicketClientAssignmentRequest;
use App\Http\Requests\Admin\TicketDocumentReviewRequest;
use App\Http\Requests\Admin\TicketFileUploadRequest;
use App\Http\Requests\Admin\TicketStageUpdateRequest;
use App\Models\Ticket;
use App\Models\TicketFile;
use App\Models\TicketStageEvent;
use App\Models\User;
use App\Services\Files\GoogleDriveFileManager;
use App\Services\Notifications\ProjectNotificationService;
use App\Services\Tickets\TicketLifecycleService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $sort = $request->query('sort') === 'created_at' ? 'created_at' : 'created_at';
        $direction = in_array($request->query('direction'), ['asc', 'desc'], true)
            ? $request->query('direction')
            : 'desc';

        return view('admin.tickets.index', [
            'tickets' => Ticket::query()
                ->with(['service', 'currentStage'])
                ->orderBy($sort, $direction)
                ->orderBy('id', $direction)
                ->paginate(15)
                ->withQueryString(),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function show(Ticket $ticket): View
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
        ]);
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
}
