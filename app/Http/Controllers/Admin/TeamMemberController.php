<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TeamMemberRequest;
use App\Models\TeamMember;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

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

    public function store(TeamMemberRequest $request): RedirectResponse
    {
        $teamMember = TeamMember::query()->create($this->payload($request));
        $this->storePhoto($request, $teamMember);

        return redirect()->route('admin.team.edit', $teamMember)->with('success', __('site.team_member_created'));
    }

    public function edit(TeamMember $teamMember): View
    {
        $teamMember->load('credentials.views');

        return view('admin.team.edit', [
            'teamMember' => $teamMember,
        ]);
    }

    public function update(TeamMemberRequest $request, TeamMember $teamMember): RedirectResponse
    {
        $teamMember->update($this->payload($request));
        $this->storePhoto($request, $teamMember);

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

    private function storePhoto(TeamMemberRequest $request, TeamMember $teamMember): void
    {
        if (! $request->hasFile('photo')) {
            return;
        }

        if ($teamMember->photo_path) {
            Storage::disk('public')->delete($teamMember->photo_path);
        }

        $teamMember->forceFill([
            'photo_path' => $request->file('photo')->store('team/photos', 'public'),
        ])->save();
    }
}
