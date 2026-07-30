<?php

namespace Tests\Feature;

use App\Mail\AdminNewTicketMail;
use App\Mail\ProjectUpdateMail;
use App\Models\BlogPost;
use App\Models\Proposal;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\Ticket;
use App\Services\Credentials\CredentialPreviewRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PublicPlatformTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Mail::fake();
        $this->seed();
    }

    public function test_public_pages_are_available(): void
    {
        $this->get('/')->assertOk();
        $this->get('/tracking')->assertOk();
        $this->get('/blog')->assertOk();
        $this->get('/login')->assertOk();
    }

    public function test_public_pages_include_expected_seo_metadata(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://ignastudio.com/">', false)
            ->assertSee('<meta property="og:title"', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertSee('application/ld+json', false)
            ->assertDontSee('noindex');

        $this->get('/blog')
            ->assertOk()
            ->assertSee('<title>'.__('site.seo_blog_title').'</title>', false)
            ->assertSee('<link rel="canonical" href="https://ignastudio.com/blog">', false);

        $this->get('/tracking')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        $this->get('/login')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_www_host_redirects_to_canonical_non_www_host(): void
    {
        $this->get('https://www.ignastudio.com/blog?source=test')
            ->assertStatus(301)
            ->assertRedirect('https://ignastudio.com/blog?source=test');
    }

    public function test_www_host_redirect_preserves_state_changing_request_method_and_query(): void
    {
        $this->post('https://www.ignastudio.com/request?source=ad', [])
            ->assertStatus(308)
            ->assertRedirect('https://ignastudio.com/request?source=ad');
    }

    public function test_seo_resources_include_public_content_and_exclude_private_surfaces(): void
    {
        $post = BlogPost::query()->create([
            'title' => 'Useful project tracking guide',
            'slug' => 'useful-project-tracking-guide',
            'summary' => 'A practical guide for client project tracking.',
            'body_html' => '<p>Project tracking content.</p>',
            'status' => 'published',
            'published_at' => now(),
        ]);

        BlogPost::query()->create([
            'title' => 'Dummy post',
            'slug' => 'dfsdf',
            'summary' => 'Placeholder content.',
            'body_html' => '<p>Placeholder content.</p>',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $sitemap = $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('content-type', 'application/xml; charset=UTF-8')
            ->assertSee('https://ignastudio.com/', false)
            ->assertSee('https://ignastudio.com/blog/'.$post->slug, false)
            ->assertDontSee('/login', false)
            ->assertDontSee('/tracking', false)
            ->assertDontSee('/blog/dfsdf', false)
            ->assertDontSee('/markdown/', false);

        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $sitemap->getContent());

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('User-agent: *')
            ->assertSee('Sitemap: https://ignastudio.com/sitemap.xml');

        $this->get('/llms.txt')
            ->assertOk()
            ->assertSee('IGNA Studio')
            ->assertSee('https://ignastudio.com/markdown/home.md')
            ->assertDontSee('dfsdf');

        $this->get('/LLMMS.pub.txt')
            ->assertStatus(301)
            ->assertRedirect('/llms.txt');

        $this->get('/markdown/home.md')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, follow')
            ->assertSee('# IGNA Studio');

        $this->get('/blog/dfsdf')->assertNotFound();
    }

    public function test_share_link_and_credential_views_are_noindex_and_excluded_from_seo_resources(): void
    {
        Storage::fake('local');

        $proposal = Proposal::query()->create([
            'proposal_number' => 'IGNA-2026-1999',
            'title' => 'Private shared proposal',
            'subject' => 'Private review link',
            'description' => 'Client-specific proposal content.',
            'scope' => 'Private scope.',
            'timeline_months' => 1,
            'timeline_weeks' => 0,
            'payment_schedule' => [
                ['label' => 'Start', 'percentage' => 100],
            ],
            'status' => 'sent',
            'tax_rate' => 0,
            'subtotal' => 100000,
            'tax_total' => 0,
            'total' => 100000,
            'issued_at' => now(),
            'valid_until' => now()->addDays(30),
            'validity_days' => 30,
        ]);

        $member = TeamMember::query()->firstOrFail();
        $path = UploadedFile::fake()->create('credential.pdf', 32, 'application/pdf')
            ->storeAs("team/credentials/{$member->slug}", 'credential.pdf', 'local');

        $credential = $member->credentials()->create([
            'title' => 'Private SEO boundary credential',
            'institution' => 'Universidad de prueba',
            'document_path' => $path,
            'original_name' => 'credential.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 32000,
            'protected_document_path' => "team/credentials/{$member->slug}/protected/credential-protected.pdf",
            'protection_status' => 'ready',
            'preview_page_count' => 0,
            'is_public' => true,
            'sort_order' => 1,
        ]);
        Storage::disk('local')->put($credential->protected_document_path, '%PDF-protected');
        $this->assertTrue($credential->refresh()->hasProtectedDerivative());
        Storage::disk('local')->assertExists($credential->protected_document_path);

        $this->get(route('proposals.public.token.show', $proposal->public_token))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertDontSee('<link rel="canonical"', false);

        $credentialUrl = URL::temporarySignedRoute('team.credentials.show', now()->addMinutes(5), [
            'teamMember' => $member,
            'credential' => $credential,
        ]);

        $this->get($credentialUrl)
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertDontSee('<link rel="canonical"', false);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertDontSee('proposals/public', false)
            ->assertDontSee('/credentials/', false);

        $this->get('/llms.txt')
            ->assertOk()
            ->assertDontSee('Private shared proposal')
            ->assertDontSee('Private SEO boundary credential');
    }

    public function test_locale_switch_changes_public_copy(): void
    {
        $this->withSession(['locale' => 'es'])
            ->get('/')
            ->assertOk()
            ->assertSee('Cuéntanos sobre el proyecto que necesitas avanzar.')
            ->assertDontSee('brief');

        $this->post(route('locale.switch', 'en'))->assertRedirect();

        $this->get('/')
            ->assertOk()
            ->assertSee('Tell us about the project you need to move forward.')
            ->assertSee('Login');
    }

    public function test_public_request_creates_a_ticket_with_workflow(): void
    {
        $service = Service::query()->firstOrFail();

        $response = $this->post(route('requests.store'), [
            'first_name' => 'Jesus',
            'last_name' => 'Castaneda',
            'email' => 'client@example.com',
            'phone' => '+57 300 123 4567',
            'project_name' => 'Proyecto Base',
            'project_location' => 'Bogota',
            'preferred_language' => 'es',
            'service_id' => $service->id,
            'project_description' => 'Proyecto de prueba para validar el flujo.',
            'target_date' => now()->addWeeks(2)->toDateString(),
        ]);

        $response->assertRedirect(route('tracking.index'));

        $ticket = Ticket::query()
            ->with('stageEvents')
            ->where('project_name', 'Proyecto Base')
            ->firstOrFail();

        $this->assertStringStartsWith('IGNA-', $ticket->ticket_code);
        $this->assertSame($service->id, $ticket->service_id);
        $this->assertCount($service->stages()->count(), $ticket->stageEvents);

        Mail::assertSent(ProjectUpdateMail::class, fn (ProjectUpdateMail $mail): bool => $mail->type === 'request_received');
        Mail::assertSent(AdminNewTicketMail::class);
    }

    public function test_public_request_rejects_inactive_services(): void
    {
        $service = Service::query()->firstOrFail();
        $service->update(['is_active' => false]);

        $this->post(route('requests.store'), [
            'first_name' => 'Jesus',
            'last_name' => 'Castaneda',
            'email' => 'client@example.com',
            'phone' => '+57 300 123 4567',
            'project_name' => 'Proyecto Base',
            'project_location' => 'Bogota',
            'preferred_language' => 'es',
            'service_id' => $service->id,
            'project_description' => 'Proyecto de prueba para validar el flujo.',
            'target_date' => now()->addWeeks(2)->toDateString(),
        ])->assertSessionHasErrors('service_id');
    }

    public function test_public_pdf_credential_view_has_protected_open_fallback(): void
    {
        Storage::fake('local');

        $source = new \FPDF;
        $source->AddPage();
        $source->SetFont('Helvetica', 'B', 18);
        $source->Cell(0, 10, 'Professional diploma');

        $member = TeamMember::query()->firstOrFail();
        $path = "team/credentials/{$member->slug}/credential.pdf";
        Storage::disk('local')->put($path, $source->Output('S'));

        $credential = $member->credentials()->create([
            'title' => 'Professional diploma',
            'institution' => 'Universidad de prueba',
            'document_path' => $path,
            'original_name' => 'credential.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => Storage::disk('local')->size($path),
            'preview_page_count' => 0,
            'is_public' => true,
            'sort_order' => 1,
        ]);
        $credential = app(CredentialPreviewRenderer::class)->generateProtectedDerivative($credential);
        $this->assertTrue($credential->hasProtectedDerivative());
        Storage::disk('local')->assertExists($credential->protected_document_path);

        $url = URL::temporarySignedRoute('team.credentials.show', now()->addMinutes(5), [
            'teamMember' => $member,
            'credential' => $credential,
        ]);

        $this->get($url)
            ->assertOk()
            ->assertSee('Professional diploma')
            ->assertSeeText(__('site.protected_pdf_inline_note'))
            ->assertSee('<object', false)
            ->assertSee('type="application/pdf"', false)
            ->assertSee('toolbar=0', false);
    }

    public function test_public_pdf_credential_file_route_returns_rasterized_derivative_only(): void
    {
        Storage::fake('local');

        $source = new \FPDF;
        $source->AddPage();
        $source->SetFont('Helvetica', 'B', 18);
        $source->Cell(0, 10, 'Original credential content');

        $member = TeamMember::query()->firstOrFail();
        $path = "team/credentials/{$member->slug}/credential.pdf";
        Storage::disk('local')->put($path, $source->Output('S'));

        $credential = $member->credentials()->create([
            'title' => 'Protected diploma',
            'institution' => 'Universidad de prueba',
            'document_path' => $path,
            'original_name' => 'credential.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => Storage::disk('local')->size($path),
            'preview_page_count' => 1,
            'is_public' => true,
            'sort_order' => 1,
        ]);
        $credential = app(CredentialPreviewRenderer::class)->generateProtectedDerivative($credential);

        $url = URL::temporarySignedRoute('team.credentials.file', now()->addMinutes(5), [
            'teamMember' => $member,
            'credential' => $credential,
        ]);

        $response = $this->get($url);

        $response
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename="credential-protected.pdf"')
            ->assertHeader('x-content-type-options', 'nosniff');

        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringNotContainsString('Original credential content', $response->getContent());
        $this->assertStringNotContainsString($credential->document_path, $response->getContent());
        Storage::disk('local')->assertExists($credential->document_path);
        Storage::disk('local')->assertExists($credential->protected_document_path);
        $this->assertNotSame($credential->original_checksum, $credential->protected_checksum);
    }

    public function test_public_credential_file_route_fails_closed_without_protected_derivative(): void
    {
        Storage::fake('local');

        $member = TeamMember::query()->firstOrFail();
        $path = UploadedFile::fake()->create('credential.pdf', 32, 'application/pdf')
            ->storeAs("team/credentials/{$member->slug}", 'credential.pdf', 'local');

        $credential = $member->credentials()->create([
            'title' => 'Unprotected credential',
            'institution' => 'Universidad de prueba',
            'document_path' => $path,
            'original_name' => 'credential.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 32000,
            'protection_status' => 'failed',
            'preview_page_count' => 0,
            'is_public' => true,
            'sort_order' => 1,
        ]);

        $url = URL::temporarySignedRoute('team.credentials.file', now()->addMinutes(5), [
            'teamMember' => $member,
            'credential' => $credential,
        ]);

        $this->get($url)->assertNotFound();
    }

    public function test_private_or_mismatched_credentials_are_not_publicly_accessible_even_with_signed_urls(): void
    {
        Storage::fake('local');

        $members = TeamMember::query()->take(2)->get();
        $this->assertCount(2, $members);

        $path = UploadedFile::fake()->create('credential.pdf', 32, 'application/pdf')
            ->storeAs("team/credentials/{$members[0]->slug}", 'credential.pdf', 'local');

        $credential = $members[0]->credentials()->create([
            'title' => 'Private credential',
            'institution' => 'Universidad de prueba',
            'document_path' => $path,
            'original_name' => 'credential.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 32000,
            'preview_page_count' => 0,
            'is_public' => false,
            'sort_order' => 1,
        ]);

        $privateUrl = URL::temporarySignedRoute('team.credentials.show', now()->addMinutes(5), [
            'teamMember' => $members[0],
            'credential' => $credential,
        ]);

        $mismatchedUrl = URL::temporarySignedRoute('team.credentials.show', now()->addMinutes(5), [
            'teamMember' => $members[1],
            'credential' => $credential,
        ]);

        $this->get($privateUrl)->assertNotFound();
        $this->get($mismatchedUrl)->assertNotFound();
    }
}
