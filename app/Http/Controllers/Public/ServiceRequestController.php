<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreServiceRequestRequest;
use App\Models\TicketFile;
use App\Services\Notifications\ProjectNotificationService;
use App\Services\Tickets\ClientDocumentSecurityService;
use App\Services\Tickets\TicketLifecycleService;
use Illuminate\Http\RedirectResponse;

class ServiceRequestController extends Controller
{
    public function store(
        StoreServiceRequestRequest $request,
        TicketLifecycleService $ticketLifecycleService,
        ClientDocumentSecurityService $documentSecurityService,
        ProjectNotificationService $projectNotificationService,
    ): RedirectResponse {
        $ticket = $ticketLifecycleService->createFromPublicRequest($request->validated());

        if ($request->hasFile('initial_file')) {
            $storedFile = $documentSecurityService->store($ticket, $request->file('initial_file'), 'initial_request');

            $file = TicketFile::query()->create([
                'ticket_id' => $ticket->id,
                'title' => __('site.initial_request_file'),
                'deliverable_type' => 'supporting_document',
                'visibility' => 'internal',
                'delivery_type' => 'internal',
                'upload_source' => 'initial_request',
                'review_status' => 'pending_review',
                'submitted_context_hash' => hash('sha256', strtolower($ticket->email)),
                'is_client_visible' => false,
                'watermark_status' => 'pending_review',
                'uploaded_at' => now(),
                ...$storedFile,
            ]);

            $projectNotificationService->notifyAdminsDocumentSubmitted($ticket, $file);
        }

        return redirect()
            ->route('tracking.index')
            ->with('success', __('site.request_success'))
            ->with('tracking_lookup', [
                'ticket_code' => $ticket->ticket_code,
                'email' => $ticket->email,
            ]);
    }
}
