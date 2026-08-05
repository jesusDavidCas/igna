<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProposalProjectRequest;
use App\Models\Proposal;
use App\Models\Service;
use App\Services\Proposals\CreateProjectFromProposal;
use Illuminate\Http\RedirectResponse;

class ProposalProjectController extends Controller
{
    public function store(
        ProposalProjectRequest $request,
        Proposal $proposal,
        CreateProjectFromProposal $createProjectFromProposal,
    ): RedirectResponse {
        if ($proposal->project()->exists()) {
            return redirect()
                ->route('admin.tickets.show', $proposal->project)
                ->with('info', __('site.proposal_project_already_exists'));
        }

        $service = Service::query()->whereKey($request->validated('service_id'))->firstOrFail();
        $ticket = $createProjectFromProposal->create($proposal, $service, $request->user());

        return redirect()
            ->route('admin.tickets.show', $ticket)
            ->with('success', __('site.proposal_project_created'));
    }
}
