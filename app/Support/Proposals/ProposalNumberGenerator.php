<?php

namespace App\Support\Proposals;

use App\Models\Proposal;

class ProposalNumberGenerator
{
    public function generate(): string
    {
        $year = now()->format('Y');
        $prefix = (string) config('igna.proposal_number.prefix', 'IGNA');
        $startingBase = (int) config('igna.proposal_number.starting_base', 1041);
        $next = Proposal::query()
            ->where('proposal_number', 'like', "{$prefix}-{$year}-%")
            ->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $year, $startingBase + $next);
    }
}
