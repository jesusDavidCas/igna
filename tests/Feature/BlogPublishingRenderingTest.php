<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\BlogPost;
use App\Models\User;
use App\Services\Blog\BlogHeaderImageManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BlogPublishingRenderingTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed();

        $this->superAdmin = User::query()->where('role', UserRole::SUPER_ADMIN)->firstOrFail();
    }

    public function test_blog_header_image_url_uses_public_laravel_route_instead_of_storage_symlink(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('blog/headers/article.jpg', $this->jpeg());

        $post = $this->blogPost(['header_image_path' => 'blog/headers/article.jpg']);

        $this->assertStringContainsString('/blog/header-proof/header-image', $post->headerImageUrl());
        $this->assertStringNotContainsString('/storage/blog/headers/article.jpg', $post->headerImageUrl());
    }

    public function test_blog_header_image_route_is_public_and_serves_valid_jpeg_png_and_webp_files(): void
    {
        Storage::fake('public');

        $files = [
            'image/jpeg' => ['path' => 'blog/headers/article.jpg', 'contents' => $this->jpeg()],
            'image/png' => ['path' => 'blog/headers/article.png', 'contents' => $this->png()],
        ];

        if (function_exists('imagewebp')) {
            $files['image/webp'] = ['path' => 'blog/headers/article.webp', 'contents' => $this->webp()];
        }

        foreach ($files as $mimeType => $file) {
            Storage::disk('public')->put($file['path'], $file['contents']);

            $post = $this->blogPost([
                'slug' => 'header-proof-'.str_replace(['image/', 'jpeg'], ['', 'jpg'], $mimeType),
                'header_image_path' => $file['path'],
            ]);

            $this->get(route('blog.header-image', ['post' => $post->slug, 'v' => $post->headerImageVersion()]))
                ->assertOk()
                ->assertHeader('content-type', $mimeType)
                ->assertHeader('content-length')
                ->assertHeader('x-content-type-options', 'nosniff')
                ->assertHeader('cache-control', 'max-age=604800, public');
        }
    }

    public function test_blog_header_image_route_fails_safely_for_missing_invalid_or_unapproved_paths(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('blog/headers/not-an-image.jpg', 'not an image');
        Storage::disk('public')->put('team/credentials/private.pdf', '%PDF private');

        $missing = $this->blogPost([
            'slug' => 'missing-header',
            'header_image_path' => 'blog/headers/missing.jpg',
        ]);
        $invalid = $this->blogPost([
            'slug' => 'invalid-header',
            'header_image_path' => 'blog/headers/not-an-image.jpg',
        ]);
        $private = $this->blogPost([
            'slug' => 'private-header',
            'header_image_path' => 'team/credentials/private.pdf',
        ]);

        $this->get(route('blog.header-image', $missing))->assertNotFound();
        $this->get(route('blog.header-image', $invalid))->assertNotFound()->assertDontSee('not an image');
        $this->get(route('blog.header-image', ['post' => $private->slug, 'path' => '../credentials/private.pdf']))
            ->assertNotFound()
            ->assertDontSee('%PDF private');
    }

    public function test_blog_header_image_manager_path_guard_accepts_only_public_blog_header_directory(): void
    {
        $manager = app(BlogHeaderImageManager::class);

        $this->assertTrue($manager->isApprovedPath('blog/headers/photo.jpg'));
        $this->assertFalse($manager->isApprovedPath('../blog/headers/photo.jpg'));
        $this->assertFalse($manager->isApprovedPath('/blog/headers/photo.jpg'));
        $this->assertFalse($manager->isApprovedPath('blog/headers/../private.jpg'));
        $this->assertFalse($manager->isApprovedPath('team/credentials/private.pdf'));
    }

    public function test_blog_header_image_cache_version_changes_after_replacement(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('blog/headers/one.jpg', $this->jpeg());
        Storage::disk('public')->put('blog/headers/two.jpg', $this->jpeg());

        $post = $this->blogPost(['header_image_path' => 'blog/headers/one.jpg']);
        $before = $post->headerImageUrl();

        Carbon::setTestNow(now()->addMinute());
        $post->forceFill(['header_image_path' => 'blog/headers/two.jpg'])->save();

        $this->assertNotSame($before, $post->fresh()->headerImageUrl());

        Carbon::setTestNow();
    }

    public function test_blog_public_article_index_and_admin_preview_use_routed_header_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('blog/headers/article.png', $this->png());

        $post = $this->blogPost(['header_image_path' => 'blog/headers/article.png']);
        $route = route('blog.header-image', ['post' => $post->slug], false);

        $this->get(route('blog.show', $post))
            ->assertOk()
            ->assertSee($route, false)
            ->assertSee('blog-article-content', false)
            ->assertSee('<h2>Semantic Heading</h2>', false)
            ->assertSee('<h3>Semantic Subheading</h3>', false)
            ->assertSee('<ul><li>Semantic item</li></ul>', false)
            ->assertDontSee('/storage/blog/headers/article.png', false);

        $this->get(route('blog.index'))
            ->assertOk()
            ->assertSee($route, false)
            ->assertDontSee('/storage/blog/headers/article.png', false);

        $this->actingAs($this->superAdmin)
            ->get(route('admin.blog.edit', $post))
            ->assertOk()
            ->assertSee($route, false)
            ->assertDontSee('/storage/blog/headers/article.png', false);
    }

    public function test_blog_article_without_valid_header_image_omits_img_cleanly(): void
    {
        Storage::fake('public');

        $post = $this->blogPost(['header_image_path' => null]);

        $this->assertNull($post->headerImageUrl());

        $this->get(route('blog.show', $post))
            ->assertOk()
            ->assertDontSee('header-image', false)
            ->assertDontSee('<img', false);
    }

    public function test_blog_html_sanitization_preserves_approved_semantic_formatting(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('admin.blog.store'), [
                'title' => 'Semantic Blog Article',
                'summary' => 'Safe semantic article content.',
                'body_html' => '<p onclick="alert(1)">Paragraph</p><h2>Heading</h2><h3>Subheading</h3><ul><li><strong>Item</strong></li></ul><script>alert(1)</script>',
                'status' => 'published',
                'published_at' => null,
                'seo_keywords' => 'semantic',
            ])
            ->assertRedirect(route('admin.blog.index'));

        $post = BlogPost::query()->where('slug', 'semantic-blog-article')->firstOrFail();

        $this->assertStringContainsString('<p>Paragraph</p>', $post->body_html);
        $this->assertStringContainsString('<h2>Heading</h2>', $post->body_html);
        $this->assertStringContainsString('<h3>Subheading</h3>', $post->body_html);
        $this->assertStringContainsString('<ul><li><strong>Item</strong></li></ul>', $post->body_html);
        $this->assertStringNotContainsString('<script', $post->body_html);
        $this->assertStringNotContainsString('onclick', $post->body_html);

        $this->get(route('blog.show', $post))
            ->assertOk()
            ->assertSee('<div class="blog-article-content', false)
            ->assertSee('<p>Paragraph</p>', false)
            ->assertSee('<h2>Heading</h2>', false)
            ->assertSee('<h3>Subheading</h3>', false)
            ->assertSee('<ul><li><strong>Item</strong></li></ul>', false)
            ->assertDontSee('alert(1)', false)
            ->assertDontSee('onclick', false);
    }

    public function test_blog_create_edit_publish_draft_and_upload_replacement_still_work(): void
    {
        Storage::fake('public');

        $this->actingAs($this->superAdmin)
            ->post(route('admin.blog.store'), [
                'title' => 'Created Blog Article',
                'summary' => 'Created article summary.',
                'header_image' => UploadedFile::fake()->image('header.png', 1200, 675),
                'body_html' => '<p>Created body.</p>',
                'status' => 'published',
                'published_at' => null,
                'seo_keywords' => 'created',
            ])
            ->assertRedirect(route('admin.blog.index'));

        $post = BlogPost::query()->where('slug', 'created-blog-article')->firstOrFail();

        $this->assertSame('published', $post->status->value);
        $this->assertNotNull($post->published_at);
        $this->assertStringStartsWith('blog/headers/', $post->header_image_path);
        Storage::disk('public')->assertExists($post->header_image_path);

        $oldPath = $post->header_image_path;

        $this->actingAs($this->superAdmin)
            ->put(route('admin.blog.update', $post), [
                'title' => 'Updated Blog Article',
                'slug' => $post->slug,
                'summary' => 'Updated article summary.',
                'header_image' => UploadedFile::fake()->image('replacement.jpg', 1200, 675),
                'body_html' => '<p>Updated body.</p>',
                'status' => 'draft',
                'published_at' => optional($post->published_at)->format('Y-m-d H:i:s'),
                'seo_keywords' => 'updated',
            ])
            ->assertRedirect(route('admin.blog.edit', $post));

        $post->refresh();

        $this->assertSame('draft', $post->status->value);
        $this->assertSame('Updated Blog Article', $post->title);
        $this->assertNotSame($oldPath, $post->header_image_path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($post->header_image_path);
    }

    public function test_invalid_blog_header_uploads_are_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs($this->superAdmin)
            ->post(route('admin.blog.store'), $this->payload([
                'header_image' => UploadedFile::fake()->createWithContent('broken.jpg', 'not a real image'),
            ]))
            ->assertSessionHasErrors('header_image');

        $this->actingAs($this->superAdmin)
            ->post(route('admin.blog.store'), $this->payload([
                'title' => 'SVG Blog Article',
                'header_image' => UploadedFile::fake()->create('vector.svg', 4, 'image/svg+xml'),
            ]))
            ->assertSessionHasErrors('header_image');
    }

    private function blogPost(array $overrides = []): BlogPost
    {
        return BlogPost::query()->create([
            'title' => 'Header Proof Article',
            'slug' => 'header-proof',
            'summary' => 'A practical article summary.',
            'header_image_path' => null,
            'body_html' => '<p>Semantic paragraph.</p><h2>Semantic Heading</h2><h3>Semantic Subheading</h3><ul><li>Semantic item</li></ul>',
            'status' => 'published',
            'published_at' => now(),
            ...$overrides,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return [
            'title' => 'Invalid Header Article',
            'summary' => 'Invalid header image summary.',
            'body_html' => '<p>Safe body.</p>',
            'status' => 'published',
            'published_at' => null,
            'seo_keywords' => 'invalid',
            ...$overrides,
        ];
    }

    private function png(): string
    {
        $image = imagecreatetruecolor(1200, 675);
        imagefill($image, 0, 0, imagecolorallocate($image, 52, 83, 66));

        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    private function jpeg(): string
    {
        $image = imagecreatetruecolor(1200, 675);
        imagefill($image, 0, 0, imagecolorallocate($image, 52, 83, 66));

        ob_start();
        imagejpeg($image);

        return (string) ob_get_clean();
    }

    private function webp(): string
    {
        $image = imagecreatetruecolor(1200, 675);
        imagefill($image, 0, 0, imagecolorallocate($image, 52, 83, 66));

        ob_start();
        imagewebp($image);

        return (string) ob_get_clean();
    }
}
