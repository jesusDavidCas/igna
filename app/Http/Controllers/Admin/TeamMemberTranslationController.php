<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TeamMemberTranslationRequest;
use App\Models\TeamMember;
use App\Services\Seo\PublicTranslationWorkflow;
use Illuminate\Http\RedirectResponse;

class TeamMemberTranslationController extends Controller
{
    public function update(TeamMemberTranslationRequest $request, TeamMember $teamMember, PublicTranslationWorkflow $workflow): RedirectResponse
    {
        $workflow->saveTeamEnglish($teamMember, $request->validated());

        return back()->with('success', __('site.translation_saved_for_review'));
    }

    public function approve(TeamMember $teamMember, PublicTranslationWorkflow $workflow): RedirectResponse
    {
        $workflow->approveTeamEnglish($teamMember, request()->user());

        return back()->with('success', __('site.translation_approved'));
    }
}
