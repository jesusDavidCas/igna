<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TeamCredentialRequest;
use App\Models\TeamCredential;
use App\Models\TeamMember;
use App\Services\Credentials\CredentialPreviewRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TeamCredentialController extends Controller
{
    public function store(TeamCredentialRequest $request, TeamMember $teamMember, CredentialPreviewRenderer $previewRenderer): RedirectResponse
    {
        $file = $request->file('document');
        $storedName = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs("team/credentials/{$teamMember->slug}", $storedName, 'local');
        $mimeType = $file->getClientMimeType();

        $credential = $teamMember->credentials()->create([
            'title' => $request->validated('title'),
            'credential_type' => null,
            'institution' => $request->validated('institution'),
            'issued_at' => $request->validated('issued_at'),
            'document_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $mimeType,
            'size_bytes' => $file->getSize(),
            'preview_page_count' => $previewRenderer->pageCount(Storage::disk('local')->path($path), $mimeType),
            'is_public' => $request->boolean('is_public'),
            'sort_order' => $request->validated('sort_order') ?? 0,
        ]);

        $credential = $previewRenderer->generateProtectedDerivative($credential);

        if ($credential->protection_status !== 'ready') {
            return redirect()
                ->route('admin.team.edit', $teamMember)
                ->with('warning', __('site.team_credential_protection_failed'));
        }

        return redirect()->route('admin.team.edit', $teamMember)->with('success', __('site.team_credential_uploaded'));
    }

    public function regenerate(TeamMember $teamMember, TeamCredential $credential, CredentialPreviewRenderer $previewRenderer): RedirectResponse
    {
        abort_unless($credential->team_member_id === $teamMember->id, 404);

        $credential = $previewRenderer->generateProtectedDerivative($credential);

        if ($credential->protection_error) {
            return redirect()
                ->route('admin.team.edit', $teamMember)
                ->with('warning', __('site.team_credential_regeneration_failed'));
        }

        return redirect()
            ->route('admin.team.edit', $teamMember)
            ->with('success', __('site.team_credential_regenerated'));
    }

    public function destroy(TeamMember $teamMember, TeamCredential $credential): RedirectResponse
    {
        abort_unless($credential->team_member_id === $teamMember->id, 404);

        Storage::disk('local')->delete(array_filter([
            $credential->document_path,
            $credential->protected_document_path,
        ]));
        $credential->delete();

        return redirect()->route('admin.team.edit', $teamMember)->with('success', __('site.team_credential_deleted'));
    }
}
