<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TicketClientAssignmentRequest;
use App\Http\Requests\Admin\TicketFileUploadRequest;
use App\Http\Requests\Admin\TicketStageUpdateRequest;
use App\Models\Ticket;
use App\Models\TicketFile;
use App\Models\User;
use App\Services\Files\GoogleDriveFileManager;
use App\Services\Tickets\TicketLifecycleService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
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
        $ticket->load([
            'client',
            'service.stages' => fn ($query) => $query->orderBy('sort_order'),
            'currentStage',
            'stageEvents.serviceStage',
            'files.uploadedBy',
        ]);

        return view('admin.tickets.show', [
            'ticket' => $ticket,
            'clients' => User::query()
                ->where('role', UserRole::CLIENT)
                ->where('is_active', true)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(),
        ]);
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

        $ticketLifecycleService->moveToStage(
            $ticket,
            $stage,
            $request->user(),
            $request->validated('notes'),
        );

        return redirect()->route('admin.tickets.show', $ticket)->with('success', __('site.ticket_stage_updated'));
    }

    public function storeFile(
        TicketFileUploadRequest $request,
        Ticket $ticket,
        GoogleDriveFileManager $googleDriveFileManager,
    ): RedirectResponse {
        try {
            $storedFile = $googleDriveFileManager->storeTicketFile($ticket, $request->file('file'));
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput($request->safe()->except('file'))
                ->withErrors(['file' => __('site.file_upload_failed')]);
        }

        TicketFile::query()->create([
            'ticket_id' => $ticket->id,
            'uploaded_by_user_id' => $request->user()->id,
            'title' => $request->validated('title'),
            'original_name' => $request->file('file')->getClientOriginalName(),
            'deliverable_type' => $request->validated('deliverable_type'),
            'is_client_visible' => $request->boolean('is_client_visible'),
            'uploaded_at' => now(),
            ...$storedFile,
        ]);

        return redirect()->route('admin.tickets.show', $ticket)->with('success', __('site.ticket_file_uploaded'));
    }

    public function updateFileVisibility(Ticket $ticket, TicketFile $file): RedirectResponse
    {
        abort_unless($file->ticket_id === $ticket->id, 404);

        $file->update([
            'is_client_visible' => ! $file->is_client_visible,
        ]);

        return redirect()->route('admin.tickets.show', $ticket)->with('success', __('site.file_visibility_updated'));
    }
}
