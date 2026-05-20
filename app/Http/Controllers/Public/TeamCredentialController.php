<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\TeamCredential;
use App\Models\TeamCredentialView;
use App\Models\TeamMember;
use App\Services\Credentials\CredentialPreviewRenderer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class TeamCredentialController extends Controller
{
    public function show(Request $request, TeamMember $teamMember, TeamCredential $credential): View
    {
        $this->authorizeCredential($teamMember, $credential);

        TeamCredentialView::query()->create([
            'team_credential_id' => $credential->id,
            'user_id' => $request->user()?->id,
            'ip_hash' => $request->ip() ? hash('sha256', $request->ip()) : null,
            'user_agent' => (string) $request->userAgent(),
            'viewed_at' => now(),
        ]);

        return view('public.team.credential', [
            'teamMember' => $teamMember,
            'credential' => $credential,
            'fileExists' => Storage::disk('local')->exists($credential->document_path),
        ]);
    }

    public function previewPage(TeamMember $teamMember, TeamCredential $credential, int $page, CredentialPreviewRenderer $previewRenderer): Response
    {
        $this->authorizeCredential($teamMember, $credential);
        abort_unless(Storage::disk('local')->exists($credential->document_path), 404);
        abort_unless($credential->hasRenderablePreview() && $page >= 1 && $page <= $credential->preview_page_count, 404);

        $jpeg = $previewRenderer->renderJpeg(
            Storage::disk('local')->path($credential->document_path),
            $credential->mime_type,
            $page,
        );

        return response($jpeg, 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Disposition' => 'inline; filename="credential-preview-'.$page.'.jpg"',
            'Cache-Control' => 'no-store, private',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive',
        ]);
    }

    public function file(TeamMember $teamMember, TeamCredential $credential, CredentialPreviewRenderer $previewRenderer): Response
    {
        $this->authorizeCredential($teamMember, $credential);
        abort_unless(Storage::disk('local')->exists($credential->document_path), 404);

        $protectedFile = $previewRenderer->renderProtectedFile(
            Storage::disk('local')->path($credential->document_path),
            $credential->mime_type,
        );

        $safeName = pathinfo(str_replace('"', '', $credential->original_name), PATHINFO_FILENAME) ?: 'credential';

        return response($protectedFile['contents'], 200, [
            'Content-Type' => $protectedFile['mime_type'],
            'Content-Disposition' => 'inline; filename="'.$safeName.'-protected.'.$protectedFile['extension'].'"',
            'Cache-Control' => 'no-store, private',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function authorizeCredential(TeamMember $teamMember, TeamCredential $credential): void
    {
        abort_unless($credential->team_member_id === $teamMember->id, 404);
        abort_unless($teamMember->is_active && $credential->is_public, 404);
    }
}
