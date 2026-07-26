<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\BlogPost;
use App\Models\Proposal;
use App\Models\User;
use App\Support\Html\HtmlSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ReleaseContentSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_blog_sanitizer_rejects_obfuscated_unsafe_links_and_preserves_safe_links(): void
    {
        $sanitizer = app(HtmlSanitizer::class);
        $html = <<<'HTML'
<p onclick="alert(1)">Body</p>
<a href=javascript:alert(1)>unquoted</a>
<a href="javascript:alert(1)">quoted</a>
<a href='JaVaScRiPt:alert(1)'>mixed</a>
<a href="java&#x73;cript:alert(1)">entity</a>
<a href="java&#x09;script:alert(1)">control</a>
<a href="java&#x200B;script:alert(1)">unicode-control</a>
<a href="  javascript:alert(1)">space</a>
<a href="data:text/html,alert(1)">data</a>
<a href="vbscript:alert(1)">vbscript</a>
<a href="file:///etc/passwd">file</a>
<a href="//untrusted.example">protocol-relative</a>
<a href="https://example.com" target="_blank">external-safe</a>
<a href="/internal/path">relative-safe</a>
HTML;

        $clean = $sanitizer->clean($html);

        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsStringIgnoringCase('javascript:', $clean);
        $this->assertStringNotContainsStringIgnoringCase('data:', $clean);
        $this->assertStringNotContainsStringIgnoringCase('vbscript:', $clean);
        $this->assertStringNotContainsStringIgnoringCase('file:', $clean);
        $this->assertStringNotContainsString('//untrusted.example', $clean);
        $this->assertStringContainsString('href="https://example.com"', $clean);
        $this->assertStringContainsString('rel="nofollow noopener noreferrer"', $clean);
        $this->assertStringContainsString('href="/internal/path"', $clean);
    }

    public function test_blog_content_is_sanitized_on_storage_and_again_on_public_render(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.blog.store'), [
                'title' => 'Release Security Article',
                'summary' => 'Safe public rendering.',
                'body_html' => '<p>Safe text</p><a href=javascript:alert(1) onclick=alert(1)>unsafe</a><a href="https://example.com">safe</a>',
                'status' => 'published',
                'published_at' => null,
                'seo_keywords' => 'security',
            ])
            ->assertRedirect(route('admin.blog.index'));

        $post = BlogPost::query()->where('slug', 'release-security-article')->firstOrFail();

        $this->assertStringNotContainsStringIgnoringCase('javascript:', $post->body_html);
        $this->assertStringNotContainsStringIgnoringCase('onclick', $post->body_html);

        $post->forceFill([
            'body_html' => '<p>Safe fallback</p><a href="java&#x73;cript:alert(1)">unsafe-direct</a>',
        ])->save();

        $this->get(route('blog.show', $post))
            ->assertOk()
            ->assertSee('Safe fallback')
            ->assertDontSee('javascript:', false)
            ->assertDontSee('onclick', false);
    }

    public function test_historical_proposal_token_is_only_public_for_shareable_statuses(): void
    {
        $proposal = $this->proposal('draft');
        $tokenUrl = route('proposals.public.token.show', $proposal->public_token);
        $signedUrl = URL::signedRoute('proposals.public.show', $proposal);

        $this->get($tokenUrl)->assertNotFound();
        $this->get($signedUrl)->assertNotFound();

        $proposal->update(['status' => 'sent']);
        $this->get($tokenUrl)->assertOk();
        $this->get($signedUrl)->assertOk();

        $proposal->update(['status' => 'rejected']);
        $this->get($tokenUrl)->assertNotFound();

        $proposal->update(['status' => 'approved']);
        $this->get($tokenUrl)->assertOk();
    }

    public function test_public_responses_include_baseline_security_headers(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    private function proposal(string $status): Proposal
    {
        return Proposal::query()->create([
            'proposal_number' => 'IGNA-2026-SECURITY',
            'title' => 'Release security proposal',
            'subject' => 'Secure public access',
            'description' => 'Description',
            'scope' => 'Scope',
            'timeline_months' => 1,
            'timeline_weeks' => 0,
            'payment_schedule' => [
                ['label' => 'Start', 'percentage' => 100],
            ],
            'status' => $status,
            'tax_rate' => 0,
            'subtotal' => 100,
            'tax_total' => 0,
            'total' => 100,
            'issued_at' => now(),
            'valid_until' => now()->addDays(30),
            'validity_days' => 30,
        ]);
    }
}
