<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreServiceRequestRequest;
use App\Models\TicketFile;
use App\Services\Notifications\ProjectNotificationService;
use App\Services\Security\TurnstileVerifier;
use App\Services\Tickets\ClientDocumentSecurityService;
use App\Services\Tickets\TicketLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ServiceRequestController extends Controller
{
    public function store(
        StoreServiceRequestRequest $request,
        TicketLifecycleService $ticketLifecycleService,
        ClientDocumentSecurityService $documentSecurityService,
        ProjectNotificationService $projectNotificationService,
        TurnstileVerifier $turnstileVerifier,
    ): RedirectResponse {
        $payload = $request->validated();

        if (filled($request->input('website'))) {
            $this->logAntiSpamEvent('request_antispam.honeypot_triggered', $request);

            return $this->antiSpamFailure($request, 'site.request_antispam_rejected');
        }

        if (config('services.turnstile.enabled')) {
            $verification = $turnstileVerifier->verify($request->input('cf-turnstile-response'), $request->ip());

            if (! $verification->accepted) {
                $event = match ($verification->reason) {
                    'configuration_error' => 'request_antispam.configuration_error',
                    'connection_failure', 'provider_http_failure', 'malformed_response' => 'request_antispam.provider_unavailable',
                    default => 'request_antispam.turnstile_rejected',
                };
                $this->logAntiSpamEvent($event, $request, ['reason' => $verification->reason]);

                return $this->antiSpamFailure(
                    $request,
                    $verification->retryable ? 'site.request_antispam_unavailable' : 'site.request_antispam_rejected',
                );
            }

            $this->logAntiSpamEvent('request_antispam.accepted', $request);
        }

        $ticket = $ticketLifecycleService->createFromPublicRequest(Arr::except($payload, ['website', 'cf-turnstile-response']));

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

    private function antiSpamFailure(StoreServiceRequestRequest $request, string $message): RedirectResponse
    {
        return back()
            ->withInput($request->except(['website', 'cf-turnstile-response', 'initial_file']))
            ->withErrors(['request' => __($message)]);
    }

    private function logAntiSpamEvent(string $event, StoreServiceRequestRequest $request, array $context = []): void
    {
        Log::notice($event, [
            'client' => hash_hmac('sha256', $request->ip(), (string) config('app.key')),
            ...$context,
        ]);
    }
}
