<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GuardedDeletionRequest;
use App\Http\Requests\Admin\TeamMemberRequest;
use App\Models\TeamMember;
use App\Services\Deletion\DeleteTeamMember;
use App\Services\Team\TeamPhotoManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class TeamMemberController extends Controller
{
    public function index(): View
    {
        return view('admin.team.index', [
            'members' => TeamMember::query()->withCount('credentials')->orderBy('sort_order')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.team.create', [
            'teamMember' => new TeamMember(['is_active' => true, 'sort_order' => 0]),
        ]);
    }

    public function store(TeamMemberRequest $request, TeamPhotoManager $photos): RedirectResponse
    {
        $teamMember = TeamMember::query()->create($this->payload($request));
        $this->storePhoto($request, $teamMember, $photos);

        return redirect()->route('admin.team.edit', $teamMember)->with('success', __('site.team_member_created'));
    }

    public function edit(TeamMember $teamMember, DeleteTeamMember $deleteTeamMember): View
    {
        $teamMember->load('credentials.views');

        return view('admin.team.edit', [
            'teamMember' => $teamMember,
            'deletionImpact' => $deleteTeamMember->impact($teamMember),
        ]);
    }

    public function destroy(GuardedDeletionRequest $request, TeamMember $teamMember, DeleteTeamMember $deleteTeamMember): RedirectResponse
    {
        $deleteTeamMember->delete($teamMember, $request->user());

        return redirect()->route('admin.team.index')->with('success', __('site.team_member_deleted'));
    }

    public function update(TeamMemberRequest $request, TeamMember $teamMember, TeamPhotoManager $photos): RedirectResponse
    {
        $teamMember->update($this->payload($request));
        $this->storePhoto($request, $teamMember, $photos);

        return redirect()->route('admin.team.edit', $teamMember)->with('success', __('site.team_member_updated'));
    }

    private function payload(TeamMemberRequest $request): array
    {
        return [
            'slug' => $request->normalizedSlug(),
            'name' => $request->validated('name'),
            'role' => $request->validated('role'),
            'short_description' => $request->validated('short_description'),
            'bio' => $request->lines('bio'),
            'expertise' => $request->lines('expertise'),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $request->validated('sort_order'),
        ];
    }

    private function storePhoto(TeamMemberRequest $request, TeamMember $teamMember, TeamPhotoManager $photos): void
    {
        if (! $request->hasFile('photo')) {
            return;
        }

        $previousPath = $teamMember->photo_path;

        $teamMember->forceFill([
            'photo_path' => $photos->store($request->file('photo')),
        ])->save();

        $photos->deleteIfUnreferenced($previousPath);
    }
}
