<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\TeamCredential;
use App\Models\TeamCredentialView;
use App\Models\TeamMember;
use App\Services\Credentials\CredentialPreviewRenderer;
use App\Support\Seo\SeoManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class TeamCredentialController extends Controller
{
    public function show(Request $request, TeamMember $teamMember, TeamCredential $credential, SeoManager $seo): View
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
            'fileExists' => $credential->hasProtectedDerivative()
                && Storage::disk('local')->exists($credential->protected_document_path),
            'seo' => $seo->meta([
                'title' => $credential->title.' | IGNA Studio',
                'description' => __('site.private_credential_meta_description'),
                'robots' => 'noindex, nofollow',
            ]),
        ]);
    }

    public function previewPage(TeamMember $teamMember, TeamCredential $credential, int $page, CredentialPreviewRenderer $previewRenderer): Response
    {
        $this->authorizeCredential($teamMember, $credential);
        abort_unless($credential->hasProtectedDerivative(), 404);
        abort_unless(Storage::disk('local')->exists($credential->protected_document_path), 404);
        abort_unless($credential->hasRenderablePreview() && $page >= 1 && $page <= $credential->preview_page_count, 404);

        $jpeg = $previewRenderer->renderJpeg(
            Storage::disk('local')->path($credential->protected_document_path),
            'application/pdf',
            $page,
        );

        return response($jpeg, 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Disposition' => 'inline; filename="credential-preview-'.$page.'.jpg"',
            'Cache-Control' => 'no-store, private',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function file(TeamMember $teamMember, TeamCredential $credential): Response
    {
        $this->authorizeCredential($teamMember, $credential);
        abort_unless($credential->hasProtectedDerivative(), 404);
        abort_unless(Storage::disk('local')->exists($credential->protected_document_path), 404);

        $safeName = pathinfo(str_replace('"', '', $credential->original_name), PATHINFO_FILENAME) ?: 'credential';
        $contents = Storage::disk('local')->get($credential->protected_document_path);

        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$safeName.'-protected.pdf"',
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
