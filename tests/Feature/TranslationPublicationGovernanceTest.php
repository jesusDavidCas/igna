<?php

namespace Tests\Feature;

use App\Enums\BlogPostStatus;
use App\Enums\TranslationStatus;
use App\Enums\UserRole;
use App\Models\BlogPost;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\Deletion\DeleteTeamMember;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TranslationPublicationGovernanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_blog_translation_lifecycle_requires_review_approval_and_a_current_source(): void
    {
        [$editor, $superAdmin] = $this->reviewers();
        $post = $this->publishedPost();

        $this->actingAs($editor)->put(route('admin.blog.translations.en.update', $post), [
            'title' => 'Approved English heading',
            'summary' => 'Approved English summary.',
            'body_html' => '<p onclick="alert(1)">Approved English article.</p>',
            'seo_title' => 'English SEO title',
            'seo_description' => 'English SEO description.',
            'submit_for_review' => true,
            'status' => 'approved',
            'approved_by_user_id' => $editor->id,
        ])->assertRedirect();

        $translation = $post->fresh()->englishTranslation;
        $this->assertSame(TranslationStatus::IN_REVIEW, $translation->status);
        $this->assertNull($translation->approved_at);
        $this->assertNull($translation->approved_by_user_id);
        $this->assertStringNotContainsString('onclick', $translation->body_html);

        $this->actingAs($editor)
            ->post(route('admin.blog.translations.en.approve', $post))
            ->assertForbidden();

        $this->actingAs($superAdmin)
            ->post(route('admin.blog.translations.en.approve', $post))
            ->assertRedirect();

        $translation = $post->fresh()->englishTranslation;
        $this->assertSame(TranslationStatus::APPROVED, $translation->status);
        $this->assertSame($superAdmin->id, $translation->approved_by_user_id);
        $this->assertTrue($translation->isApprovedFor($post->fresh()->spanishSourceFingerprint()));

        config()->set('igna.seo.english_publication.enabled', true);
        config()->set('igna.seo.english_publication.blog_slugs', [$post->slug]);
        $this->get('/en/blog/'.$post->slug)->assertOk()->assertSee('Approved English heading');

        $post->update(['title' => 'Changed Spanish heading']);
        $this->assertNotNull($translation->fresh()->stale_at);
        $this->get('/en/blog/'.$post->slug)->assertNotFound();

        $post->update(['title' => 'Spanish source heading']);
        $this->assertNotNull($translation->fresh()->stale_at);
        $this->get('/en/blog/'.$post->slug)->assertNotFound();

        $this->actingAs($superAdmin)
            ->post(route('admin.blog.translations.en.approve', $post))
            ->assertSessionHasErrors('status');

        $this->actingAs($editor)->put(route('admin.blog.translations.en.update', $post), [
            'title' => 'Approved English heading, revised',
            'summary' => 'Approved English summary, revised.',
            'body_html' => '<p>Approved English article, revised.</p>',
            'submit_for_review' => true,
        ])->assertRedirect();
        $translation = $translation->fresh();
        $this->assertSame(TranslationStatus::IN_REVIEW, $translation->status);
        $this->assertNull($translation->approved_by_user_id);
        $this->assertNull($translation->approved_at);
        $this->actingAs($superAdmin)->post(route('admin.blog.translations.en.approve', $post))->assertRedirect();
        $this->get('/en/blog/'.$post->slug)->assertOk()->assertSee('Approved English heading, revised');
    }

    public function test_current_fingerprint_blocks_publication_even_when_a_source_update_bypasses_events(): void
    {
        [, $superAdmin] = $this->reviewers();
        $post = $this->publishedPost();
        $translation = $post->englishTranslation()->create([
            'locale' => 'en',
            'title' => 'English heading',
            'summary' => 'English summary',
            'body_html' => '<p>English body</p>',
            'status' => TranslationStatus::IN_REVIEW,
            'source_fingerprint' => $post->spanishSourceFingerprint(),
            'stale_at' => now(),
        ]);
        $this->actingAs($superAdmin)->post(route('admin.blog.translations.en.approve', $post))->assertRedirect();

        config()->set('igna.seo.english_publication.enabled', true);
        config()->set('igna.seo.english_publication.blog_slugs', [$post->slug]);
        $this->get('/en/blog/'.$post->slug)->assertOk();

        DB::table('blog_posts')->where('id', $post->id)->update(['title' => 'Changed outside the model']);
        $this->assertSame('Changed outside the model', $post->fresh()->title);
        $this->assertNull($translation->fresh()->stale_at);
        $this->get('/en/blog/'.$post->slug)->assertNotFound();
    }

    public function test_team_translation_requires_super_admin_review_and_becomes_stale_when_source_changes(): void
    {
        [$editor, $superAdmin] = $this->reviewers();
        $member = TeamMember::query()->create([
            'slug' => 'governance-team-member',
            'name' => 'Miembro de equipo',
            'is_active' => true,
            'role' => 'Rol en espanol',
            'short_description' => 'Resumen en espanol.',
            'bio' => ['Biografia en espanol.'],
            'expertise' => ['Ingenieria'],
        ]);

        $this->actingAs($editor)->put(route('admin.team.translations.en.update', $member), [
            'role' => 'Engineering lead',
            'short_description' => 'English team summary.',
            'bio' => "English biography one.\nEnglish biography two.",
            'expertise' => "Delivery\nEngineering",
            'submit_for_review' => true,
        ])->assertRedirect();

        $this->actingAs($editor)->post(route('admin.team.translations.en.approve', $member))->assertForbidden();
        $this->actingAs($superAdmin)->post(route('admin.team.translations.en.approve', $member))->assertRedirect();

        config()->set('igna.seo.english_publication.enabled', true);
        $this->get('/en/team/'.$member->slug)
            ->assertOk()
            ->assertSee('English team summary.')
            ->assertSee('<link rel="alternate" hreflang="es" href="https://ignastudio.com/team/'.$member->slug.'">', false)
            ->assertSee('<link rel="alternate" hreflang="en" href="https://ignastudio.com/en/team/'.$member->slug.'">', false);
        $this->get('/sitemap.xml')->assertSee('https://ignastudio.com/en/team/'.$member->slug, false);

        $member->update(['role' => 'Changed Spanish role']);
        $this->get('/en/team/'.$member->slug)->assertNotFound();
    }

    public function test_translation_edits_require_a_valid_csrf_token(): void
    {
        [$editor] = $this->reviewers();
        $post = $this->publishedPost();
        $this->app->instance('env', 'local');

        $this->withMiddleware(PreventRequestForgery::class)
            ->actingAs($editor)
            ->call('PUT', route('admin.blog.translations.en.update', $post), [
                'title' => 'English title',
                'summary' => 'English summary',
                'body_html' => '<p>English body</p>',
            ])
            ->assertStatus(419);

        $this->assertNull($post->fresh()->englishTranslation);
    }

    public function test_translation_editor_routes_deny_guests_and_clients_and_reject_unsupported_methods(): void
    {
        [$editor] = $this->reviewers();
        $client = User::factory()->create(['role' => UserRole::CLIENT, 'is_active' => true]);
        $post = $this->publishedPost();
        $updateRoute = route('admin.blog.translations.en.update', $post);

        $this->put($updateRoute, [])->assertRedirect(route('login'));
        $this->actingAs($client)->put($updateRoute, [])->assertForbidden();
        $this->actingAs($editor)->get($updateRoute)->assertStatus(405);
        $this->actingAs($editor)->post($updateRoute)->assertStatus(405);
    }

    public function test_empty_translation_tables_can_be_rolled_back_and_reapplied_without_touching_source_content(): void
    {
        $post = $this->publishedPost();
        $migration = require database_path('migrations/2026_10_22_000100_create_public_content_translation_tables.php');

        $migration->down();
        $this->assertFalse(Schema::hasTable('blog_post_translations'));
        $this->assertFalse(Schema::hasTable('team_member_translations'));
        $migration->up();
        $this->assertTrue(Schema::hasTable('blog_post_translations'));
        $this->assertSame('Spanish source heading', $post->fresh()->title);

    }

    public function test_populated_blog_translation_refuses_rollback_before_either_table_is_dropped(): void
    {
        $post = $this->publishedPost();
        $post->englishTranslation()->create([
            'locale' => 'en', 'title' => 'Title', 'summary' => 'Summary', 'body_html' => '<p>Body</p>',
        ]);

        $this->assertPopulatedRollbackIsRefused();
        $this->assertDatabaseHas('blog_post_translations', ['blog_post_id' => $post->id, 'locale' => 'en']);
    }

    public function test_populated_team_translation_refuses_rollback_before_either_table_is_dropped(): void
    {
        $member = $this->teamMember('rollback-team-only');
        $member->translations()->create([
            'locale' => 'en', 'role' => 'Role', 'short_description' => 'Summary',
            'bio' => ['Biography'], 'expertise' => ['Expertise'],
        ]);

        $this->assertPopulatedRollbackIsRefused();
        $this->assertDatabaseHas('team_member_translations', ['team_member_id' => $member->id, 'locale' => 'en']);
    }

    public function test_both_populated_translation_tables_refuse_rollback_without_partial_drop(): void
    {
        $post = $this->publishedPost();
        $member = $this->teamMember('rollback-both');
        $post->englishTranslation()->create([
            'locale' => 'en', 'title' => 'Title', 'summary' => 'Summary', 'body_html' => '<p>Body</p>',
        ]);
        $member->translations()->create([
            'locale' => 'en', 'role' => 'Role', 'short_description' => 'Summary',
            'bio' => ['Biography'], 'expertise' => ['Expertise'],
        ]);

        $this->assertPopulatedRollbackIsRefused();
        $this->assertDatabaseHas('blog_post_translations', ['blog_post_id' => $post->id, 'locale' => 'en']);
        $this->assertDatabaseHas('team_member_translations', ['team_member_id' => $member->id, 'locale' => 'en']);
    }

    public function test_translation_foreign_keys_and_unique_parent_locale_are_enforced(): void
    {
        $post = $this->publishedPost();
        $post->englishTranslation()->create([
            'locale' => 'en', 'title' => 'Title', 'summary' => 'Summary', 'body_html' => '<p>Body</p>',
        ]);

        try {
            $post->englishTranslation()->create([
                'locale' => 'en', 'title' => 'Duplicate', 'summary' => 'Duplicate', 'body_html' => '<p>Duplicate</p>',
            ]);
            $this->fail('The parent and locale pair must be unique.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }

        $this->expectException(QueryException::class);
        DB::table('blog_post_translations')->insert([
            'blog_post_id' => 999999,
            'locale' => 'fr',
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_team_translation_foreign_key_blocks_uncontrolled_deletion_but_the_audited_service_removes_it(): void
    {
        [, $superAdmin] = $this->reviewers();
        $member = TeamMember::query()->create([
            'slug' => 'controlled-translation-deletion',
            'name' => 'Perfil controlado',
            'role' => 'Rol',
            'short_description' => 'Resumen',
            'bio' => ['Biografia'],
            'expertise' => ['Experiencia'],
            'is_active' => true,
        ]);
        $member->translations()->create([
            'locale' => 'en',
            'role' => 'Role',
            'short_description' => 'Summary',
            'bio' => ['Biography'],
            'expertise' => ['Expertise'],
        ]);

        try {
            DB::table('team_members')->where('id', $member->id)->delete();
            $this->fail('Team translations must not be silently cascaded by direct deletion.');
        } catch (QueryException) {
            $this->assertDatabaseHas('team_member_translations', ['team_member_id' => $member->id]);
        }

        $this->assertTrue(app(DeleteTeamMember::class)->delete($member, $superAdmin));
        $this->assertDatabaseMissing('team_members', ['id' => $member->id]);
        $this->assertDatabaseMissing('team_member_translations', ['team_member_id' => $member->id]);
    }

    public function test_team_deletion_service_remains_compatible_when_no_translation_exists(): void
    {
        [, $superAdmin] = $this->reviewers();
        $member = $this->teamMember('no-translation-deletion');

        $this->assertTrue(app(DeleteTeamMember::class)->delete($member, $superAdmin));
        $this->assertDatabaseMissing('team_members', ['id' => $member->id]);
    }

    public function test_public_eligibility_requires_complete_current_approval_metadata_and_available_source(): void
    {
        [, $superAdmin] = $this->reviewers();
        $post = $this->publishedPost();
        $fingerprint = $post->spanishSourceFingerprint();
        $translation = $post->englishTranslation()->create([
            'locale' => 'en', 'title' => 'English title', 'summary' => 'English summary', 'body_html' => '<p>English body</p>',
            'status' => TranslationStatus::APPROVED,
            'source_fingerprint' => $fingerprint,
            'approved_source_fingerprint' => $fingerprint,
            'approved_at' => now(),
        ]);

        config()->set('igna.seo.english_publication.enabled', true);
        config()->set('igna.seo.english_publication.blog_slugs', [$post->slug]);
        $this->get('/en/blog/'.$post->slug)->assertNotFound();

        $translation->update(['approved_by_user_id' => $superAdmin->id]);
        $this->get('/en/blog/'.$post->slug)->assertOk();

        $post->update(['header_image_path' => 'blog-headers/nonmaterial.jpg']);
        $this->assertNull($translation->fresh()->stale_at);
        $this->get('/en/blog/'.$post->slug)->assertOk();

        $post->update(['status' => BlogPostStatus::DRAFT]);
        $this->get('/en/blog/'.$post->slug)->assertNotFound();

        $post->update(['status' => BlogPostStatus::PUBLISHED]);
        $post->delete();
        $this->get('/en/blog/'.$post->slug)->assertNotFound();
    }

    public function test_inactive_team_source_is_not_eligible_even_with_complete_approval_metadata(): void
    {
        [, $superAdmin] = $this->reviewers();
        $member = $this->teamMember('inactive-approved-team-member');
        $fingerprint = $member->spanishSourceFingerprint();
        $member->translations()->create([
            'locale' => 'en', 'role' => 'Role', 'short_description' => 'Summary',
            'bio' => ['Biography'], 'expertise' => ['Expertise'],
            'status' => TranslationStatus::APPROVED,
            'source_fingerprint' => $fingerprint,
            'approved_source_fingerprint' => $fingerprint,
            'approved_by_user_id' => $superAdmin->id,
            'approved_at' => now(),
        ]);

        config()->set('igna.seo.english_publication.enabled', true);
        $this->get('/en/team/'.$member->slug)->assertOk();

        $member->update(['is_active' => false]);
        $this->get('/en/team/'.$member->slug)->assertNotFound();
    }

    private function publishedPost(): BlogPost
    {
        return BlogPost::query()->create([
            'title' => 'Spanish source heading',
            'slug' => 'translation-governance-source',
            'summary' => 'Spanish source summary.',
            'body_html' => '<p>Spanish source article.</p>',
            'status' => BlogPostStatus::PUBLISHED,
            'published_at' => now(),
        ]);
    }

    private function assertPopulatedRollbackIsRefused(): void
    {
        $migration = require database_path('migrations/2026_10_22_000100_create_public_content_translation_tables.php');

        try {
            $migration->down();
            $this->fail('Populated translation tables must refuse rollback.');
        } catch (\RuntimeException) {
            $this->assertTrue(Schema::hasTable('blog_post_translations'));
            $this->assertTrue(Schema::hasTable('team_member_translations'));
        }
    }

    private function teamMember(string $slug): TeamMember
    {
        return TeamMember::query()->create([
            'slug' => $slug,
            'name' => 'Miembro de equipo',
            'role' => 'Rol en espanol',
            'short_description' => 'Resumen en espanol.',
            'bio' => ['Biografia en espanol.'],
            'expertise' => ['Ingenieria'],
            'is_active' => true,
        ]);
    }

    /** @return array{0: User, 1: User} */
    private function reviewers(): array
    {
        return [
            User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => true]),
            User::factory()->create(['role' => UserRole::SUPER_ADMIN, 'is_active' => true]),
        ];
    }
}
