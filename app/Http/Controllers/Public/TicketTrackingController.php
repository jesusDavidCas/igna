<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\TrackTicketRequest;
use App\Models\Ticket;
use App\Support\Seo\SeoManager;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\URL;

class TicketTrackingController extends Controller
{
    public function index(SeoManager $seo): View
    {
        $ticket = null;
        $lookup = session('tracking_lookup');

        if (is_array($lookup)) {
            $ticket = $this->resolveTicket($lookup['ticket_code'], $lookup['email']);
        }

        return view('public.tracking', [
            'ticket' => $ticket,
            'submittedFiles' => $this->submittedFiles($ticket),
            'trackingUploadUrl' => $this->trackingUploadUrl($ticket),
            'seo' => $seo->meta([
                'title' => __('site.seo_tracking_title'),
                'description' => __('site.seo_tracking_description'),
                'canonical' => $seo->canonicalUrl('/tracking'),
                'robots' => 'noindex, nofollow',
            ]),
        ]);
    }

    public function show(TrackTicketRequest $request): View
    {
        $ticket = $this->resolveTicket(
            $request->validated('ticket_code'),
            $request->validated('email'),
        );

        return view('public.tracking', [
            'ticket' => $ticket,
            'submittedFiles' => $this->submittedFiles($ticket),
            'trackingUploadUrl' => $this->trackingUploadUrl($ticket),
            'seo' => app(SeoManager::class)->meta([
                'title' => __('site.seo_tracking_title'),
                'description' => __('site.seo_tracking_description'),
                'canonical' => app(SeoManager::class)->canonicalUrl('/tracking'),
                'robots' => 'noindex, nofollow',
            ]),
        ]);
    }

    private function resolveTicket(string $ticketCode, string $email): ?Ticket
    {
        return Ticket::query()
            ->with([
                'service',
                'currentStage',
                'stageEvents.serviceStage',
                'files' => fn ($query) => $query->clientVisible(),
                'deliverables.files' => fn ($query) => $query->clientVisible(),
            ])
            ->where('ticket_code', strtoupper($ticketCode))
            ->where('email', $email)
            ->first();
    }

    private function submittedFiles(?Ticket $ticket)
    {
        if (! $ticket) {
            return collect();
        }

        return $ticket->files()
            ->clientSubmitted()
            ->where('submitted_context_hash', hash('sha256', strtolower($ticket->email)))
            ->latest('uploaded_at')
            ->get();
    }

    private function trackingUploadUrl(?Ticket $ticket): ?string
    {
        if (! $ticket) {
            return null;
        }

        return URL::temporarySignedRoute('tracking.documents.store', now()->addMinutes(30), [
            'ticket' => $ticket,
            'email_hash' => hash('sha256', strtolower($ticket->email)),
        ]);
    }
}
