<?php

namespace App\Http\Controllers;

use App\Events\TicketClientDocumentUploaded;
use App\Http\Requests\TicketClientDocumentUploadRequest;
use App\Models\Ticket;
use App\Models\TicketFile;
use App\Services\Tickets\ClientDocumentSecurityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class TicketClientDocumentController extends Controller
{
    public function client(
        TicketClientDocumentUploadRequest $request,
        Ticket $ticket,
        ClientDocumentSecurityService $documentSecurityService,
    ): RedirectResponse {
        abort_unless($ticket->client_user_id === $request->user()->id, 404);

        $this->storeClientDocument(
            request: $request,
            ticket: $ticket,
            documentSecurityService: $documentSecurityService,
            source: 'authenticated_client',
            uploadedByUserId: $request->user()->id,
            contextHash: hash('sha256', strtolower($ticket->email)),
        );

        return back()->with('success', __('site.authenticated_document_received_successfully'));
    }

    public function tracking(
        TicketClientDocumentUploadRequest $request,
        Ticket $ticket,
        ClientDocumentSecurityService $documentSecurityService,
    ): RedirectResponse {
        abort_unless($request->hasValidSignature(), 403);

        $emailHash = (string) $request->query('email_hash', '');
        abort_unless(hash_equals(hash('sha256', strtolower($ticket->email)), $emailHash), 404);

        $this->storeClientDocument(
            request: $request,
            ticket: $ticket,
            documentSecurityService: $documentSecurityService,
            source: 'public_tracking',
            uploadedByUserId: null,
            contextHash: $emailHash,
        );

        $request->session()->forget('tracking_lookup');

        return redirect()
            ->route('tracking.index')
            ->with('success', __('site.tracking_document_received_successfully'));
    }

    private function storeClientDocument(
        TicketClientDocumentUploadRequest $request,
        Ticket $ticket,
        ClientDocumentSecurityService $documentSecurityService,
        string $source,
        ?int $uploadedByUserId,
        string $contextHash,
    ): TicketFile {
        return DB::transaction(function () use ($request, $ticket, $documentSecurityService, $source, $uploadedByUserId, $contextHash): TicketFile {
            $storedFile = $documentSecurityService->store($ticket, $request->file('document'), $source);

            $file = TicketFile::query()->create([
                'ticket_id' => $ticket->id,
                'uploaded_by_user_id' => $uploadedByUserId,
                'ticket_deliverable_id' => null,
                'title' => __('site.ticket_file_category_'.$request->validated('category')),
                'deliverable_type' => $request->validated('category'),
                'visibility' => 'internal',
                'delivery_type' => 'internal',
                'upload_source' => $source,
                'review_status' => 'pending_review',
                'submitted_context_hash' => $contextHash,
                'is_client_visible' => false,
                'watermark_status' => 'pending_review',
                'uploaded_at' => now(),
                ...$storedFile,
            ]);

            event(new TicketClientDocumentUploaded(
                ticketId: $ticket->id,
                ticketFileId: $file->id,
                uploadSource: $source,
                uploadedByUserId: $uploadedByUserId,
            ));

            return $file;
        });
    }
}
