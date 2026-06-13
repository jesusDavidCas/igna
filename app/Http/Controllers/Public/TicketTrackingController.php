<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\TrackTicketRequest;
use App\Models\Ticket;
use App\Support\Seo\SeoManager;
use Illuminate\Contracts\View\View;

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
                'files' => fn ($query) => $query->where('is_client_visible', true),
                'deliverables.files' => fn ($query) => $query->where('is_client_visible', true),
            ])
            ->where('ticket_code', strtoupper($ticketCode))
            ->where('email', $email)
            ->first();
    }
}
