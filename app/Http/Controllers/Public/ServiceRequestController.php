<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreServiceRequestRequest;
use App\Models\TicketFile;
use App\Services\Files\GoogleDriveFileManager;
use App\Services\Tickets\TicketLifecycleService;
use Illuminate\Http\RedirectResponse;

class ServiceRequestController extends Controller
{
    public function store(
        StoreServiceRequestRequest $request,
        TicketLifecycleService $ticketLifecycleService,
        GoogleDriveFileManager $googleDriveFileManager,
    ): RedirectResponse {
        $ticket = $ticketLifecycleService->createFromPublicRequest($request->validated());

        if ($request->hasFile('initial_file')) {
            $storedFile = $googleDriveFileManager->storeTicketFile($ticket, $request->file('initial_file'));

            TicketFile::query()->create([
                'ticket_id' => $ticket->id,
                'title' => __('site.initial_request_file'),
                'original_name' => $request->file('initial_file')->getClientOriginalName(),
                'deliverable_type' => 'initial_request',
                'is_client_visible' => false,
                'uploaded_at' => now(),
                ...$storedFile,
            ]);
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
