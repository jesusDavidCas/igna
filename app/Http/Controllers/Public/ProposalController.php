<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Support\Seo\SeoManager;
use App\Support\Settings\BrandSettings;
use Illuminate\Contracts\View\View;

class ProposalController extends Controller
{
    public function show(Proposal $proposal, BrandSettings $brandSettings, SeoManager $seo): View
    {
        return view('public.proposals.show', [
            'proposal' => $proposal->load(['client', 'signer', 'items']),
            'brand' => $brandSettings->publicPayload(),
            'seo' => $this->proposalSeo($proposal, $seo),
        ]);
    }

    // The showByToken method fetches a proposal using a cryptographically random, non-guessable token.
    // This allows clients to view proposals without requiring authentication, while preventing sequence enumeration.
    public function showByToken(string $publicToken, BrandSettings $brandSettings, SeoManager $seo): View
    {
        $proposal = Proposal::query()
            ->where('public_token', $publicToken)
            ->firstOrFail();

        return view('public.proposals.show', [
            'proposal' => $proposal->load(['client', 'signer', 'items']),
            'brand' => $brandSettings->publicPayload(),
            'seo' => $this->proposalSeo($proposal, $seo),
        ]);
    }

    private function proposalSeo(Proposal $proposal, SeoManager $seo): array
    {
        return $seo->meta([
            'title' => $proposal->proposal_number.' | IGNA Studio',
            'description' => __('site.private_proposal_meta_description'),
            'robots' => 'noindex, nofollow',
        ]);
    }
}
