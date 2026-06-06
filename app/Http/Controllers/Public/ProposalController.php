<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Support\Settings\BrandSettings;
use Illuminate\Contracts\View\View;

class ProposalController extends Controller
{
    public function show(Proposal $proposal, BrandSettings $brandSettings): View
    {
        return view('public.proposals.show', [
            'proposal' => $proposal->load(['client', 'signer', 'items']),
            'brand' => $brandSettings->publicPayload(),
        ]);
    }

    public function showByToken(string $publicToken, BrandSettings $brandSettings): View
    {
        $proposal = Proposal::query()
            ->where('public_token', $publicToken)
            ->firstOrFail();

        return view('public.proposals.show', [
            'proposal' => $proposal->load(['client', 'signer', 'items']),
            'brand' => $brandSettings->publicPayload(),
        ]);
    }
}
