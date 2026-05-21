<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StageEventStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TicketClientAssignmentRequest;
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
use Illuminate\Support\Facades\Storage;
use Throwable;

class TicketController extends Controller
{
    public function index(): View
    {
        return view('admin.tickets.index', [
            'tickets' => Ticket::query()
                ->with(['service', 'currentStage'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function show(Ticket $ticket): View
    {
        app(TicketLifecycleService::class)->ensureDeliverables($ticket);

        $ticket->load([
            'client',
            'service.stages' => fn ($query) => $query->orderBy('sort_order'),
            'currentStage',
            'stageEvents.serviceStage',
            'files.uploadedBy',
            'deliverables.files',
        ]);

        $orderedStages = $ticket->service->stages->values();
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
        $previousStage = $ticket->service->stages()
            ->where('service_stages.id', $request->validated('service_stage_id'))
            ->firstOrFail();
        abort_unless($this->adjacentStage($ticket, -1)?->id === $previousStage->id, 422);

        $ticketLifecycleService->moveToStage(
            $ticket,
            $previousStage,
            $request->user(),
            __('site.admin_correction_note').($request->filled('notes') ? "\n".$request->validated('notes') : ''),
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
        $stage = $ticket->service->stages()
            ->where('service_stages.id', $request->validated('service_stage_id'))
            ->firstOrFail();
        abort_unless($this->adjacentStage($ticket, 1)?->id === $stage->id, 422);

        $ticketLifecycleService->moveToStage(
            $ticket,
            $stage,
            $request->user(),
            $request->validated('notes'),
        );

        return redirect()->route('admin.tickets.show', $ticket)->with('success', __('site.ticket_stage_updated'));
    }

    private function adjacentStage(Ticket $ticket, int $offset): mixed
    {
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
        ProjectNotificationService $projectNotificationService,
    ): RedirectResponse {
        abort_unless($event->ticket_id === $ticket->id, 404);

        $event->update([
            'status' => $event->service_stage_id === $ticket->current_service_stage_id
                ? StageEventStatus::CURRENT
                : StageEventStatus::PENDING,
            'completed_at' => null,
            'changed_by_user_id' => $request->user()->id,
            'notes' => trim(($event->notes ? $event->notes."\n\n" : '').'['.now()->format('Y-m-d H:i').'] '.__('site.admin_correction_note')),
        ]);

        $projectNotificationService->notifyTicket(
            $ticket,
            'stage_reopened',
            __('site.email_stage_reopened_headline', ['stage' => $event->serviceStage->localizedName()]),
            $request->validated('notes') ?: __('site.email_stage_reopened_message'),
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
                __('site.email_file_available_headline'),
                __('site.email_file_available_message', ['file' => $file->title]),
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
                __('site.email_file_available_headline'),
                __('site.email_file_available_message', ['file' => $file->title]),
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
}
