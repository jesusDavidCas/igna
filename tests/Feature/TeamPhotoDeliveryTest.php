<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\Team\TeamPhotoManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TeamPhotoDeliveryTest extends TestCase
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

    public function test_team_photo_url_uses_public_laravel_route_instead_of_storage_symlink(): void
    {
        $member = $this->member(['photo_path' => 'team/photos/member.jpg']);

        $this->assertStringContainsString('/team/photo-proof/photo', $member->photoUrl());
        $this->assertStringNotContainsString('/storage/team/photos/member.jpg', $member->photoUrl());
    }

    public function test_public_route_serves_valid_team_photo_without_authentication(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('team/photos/member.png', $this->png());

        $member = $this->member(['photo_path' => 'team/photos/member.png']);

        $this->get(route('team.photo', ['teamMember' => $member->slug, 'v' => $member->photoVersion()]))
            ->assertOk()
            ->assertHeader('content-type', 'image/png')
            ->assertHeader('content-length')
            ->assertHeader('x-content-type-options', 'nosniff');
    }

    public function test_public_route_serves_supported_jpeg_png_and_webp_photo_files(): void
    {
        Storage::fake('public');

        $files = [
            'image/jpeg' => ['path' => 'team/photos/member.jpg', 'contents' => $this->jpeg()],
            'image/png' => ['path' => 'team/photos/member.png', 'contents' => $this->png()],
        ];

        if (function_exists('imagewebp')) {
            $files['image/webp'] = ['path' => 'team/photos/member.webp', 'contents' => $this->webp()];
        }

        foreach ($files as $mimeType => $file) {
            Storage::disk('public')->put($file['path'], $file['contents']);

            $member = $this->member([
                'slug' => 'photo-proof-'.str_replace(['image/', 'jpeg'], ['', 'jpg'], $mimeType),
                'photo_path' => $file['path'],
            ]);

            $this->get(route('team.photo', ['teamMember' => $member->slug]))
                ->assertOk()
                ->assertHeader('content-type', $mimeType);
        }
    }

    public function test_public_photo_route_fails_closed_for_inactive_team_member(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('team/photos/member.png', $this->png());

        $member = $this->member([
            'photo_path' => 'team/photos/member.png',
            'is_active' => false,
        ]);

        $this->get(route('team.photo', ['teamMember' => $member->slug]))
            ->assertNotFound();
    }

    public function test_public_photo_route_falls_back_when_stored_file_is_missing_or_invalid(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('team/photos/not-an-image.jpg', 'not an image');

        $missing = $this->member([
            'slug' => 'missing-photo',
            'name' => 'Missing Photo',
            'photo_path' => 'team/photos/missing.jpg',
        ]);
        $invalid = $this->member([
            'slug' => 'invalid-photo',
            'name' => 'Invalid Photo',
            'photo_path' => 'team/photos/not-an-image.jpg',
        ]);

        $this->get(route('team.photo', ['teamMember' => $missing->slug]))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');

        $this->get(route('team.photo', ['teamMember' => $invalid->slug]))
            ->assertOk()
            ->assertHeader('content-type', 'image/png')
            ->assertDontSee('not-an-image.jpg');
    }

    public function test_route_rejects_unapproved_or_private_photo_paths(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('team/credentials/private.pdf', '%PDF private');

        $member = $this->member(['photo_path' => 'team/credentials/private.pdf']);

        $this->get(route('team.photo', ['teamMember' => $member->slug, 'path' => '../private.pdf']))
            ->assertOk()
            ->assertHeader('content-type', 'image/png')
            ->assertDontSee('%PDF private');
    }

    public function test_photo_cache_version_changes_after_photo_replacement(): void
    {
        $member = $this->member(['photo_path' => 'team/photos/one.jpg']);
        $before = $member->photoUrl();

        Carbon::setTestNow(now()->addMinute());
        $member->forceFill(['photo_path' => 'team/photos/two.jpg'])->save();

        $this->assertNotSame($before, $member->fresh()->photoUrl());

        Carbon::setTestNow();
    }

    public function test_admin_upload_is_validated_normalized_and_replaces_unshared_previous_photo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('team/photos/old.jpg', $this->png());

        $member = $this->member(['photo_path' => 'team/photos/old.jpg']);

        $this->actingAs($this->superAdmin)
            ->put(route('admin.team.update', $member), $this->payload([
                'photo' => UploadedFile::fake()->image('replacement.png', 800, 1000),
            ]))
            ->assertRedirect(route('admin.team.edit', $member));

        $member->refresh();

        $this->assertStringStartsWith('team/photos/', $member->photo_path);
        $this->assertStringEndsWith('.jpg', $member->photo_path);
        Storage::disk('public')->assertExists($member->photo_path);
        Storage::disk('public')->assertMissing('team/photos/old.jpg');

        $this->assertSame('image/jpeg', getimagesizefromstring(Storage::disk('public')->get($member->photo_path))['mime']);
    }

    public function test_photo_replacement_preserves_shared_previous_photo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('team/photos/shared.jpg', $this->png());

        $member = $this->member(['photo_path' => 'team/photos/shared.jpg']);
        $this->member([
            'slug' => 'shared-owner',
            'name' => 'Shared Owner',
            'photo_path' => 'team/photos/shared.jpg',
        ]);

        $this->actingAs($this->superAdmin)
            ->put(route('admin.team.update', $member), $this->payload([
                'photo' => UploadedFile::fake()->image('replacement.jpg', 700, 700),
            ]))
            ->assertRedirect(route('admin.team.edit', $member));

        Storage::disk('public')->assertExists('team/photos/shared.jpg');
    }

    public function test_invalid_team_photo_uploads_are_rejected(): void
    {
        Storage::fake('public');

        $member = $this->member();

        $this->actingAs($this->superAdmin)
            ->put(route('admin.team.update', $member), $this->payload([
                'photo' => UploadedFile::fake()->image('tiny.jpg', 200, 200),
            ]))
            ->assertSessionHasErrors('photo');

        $this->actingAs($this->superAdmin)
            ->put(route('admin.team.update', $member), $this->payload([
                'photo' => UploadedFile::fake()->createWithContent('broken.jpg', 'not a real image'),
            ]))
            ->assertSessionHasErrors('photo');
    }

    public function test_public_views_use_responsive_route_backed_photo_markup(): void
    {
        $member = $this->member(['photo_path' => 'team/photos/member.jpg']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('team.photo', ['teamMember' => $member->slug]), false)
            ->assertSee('aspect-[4/5]', false)
            ->assertSee('loading="lazy"', false)
            ->assertDontSee('/storage/team/photos/member.jpg', false);

        $this->get(route('team.show', $member->slug))
            ->assertOk()
            ->assertSee(route('team.photo', ['teamMember' => $member->slug]), false)
            ->assertSee('decoding="async"', false)
            ->assertDontSee('/storage/team/photos/member.jpg', false);
    }

    public function test_public_views_render_initials_without_img_when_photo_path_is_empty(): void
    {
        $member = $this->member([
            'slug' => 'initials-only',
            'name' => 'Initials Only',
            'photo_path' => null,
        ]);

        $this->get(route('team.show', $member->slug))
            ->assertOk()
            ->assertSee('IO')
            ->assertSee(__('site.team_photo_fallback_label', ['name' => 'Initials Only']))
            ->assertDontSee('<img', false);
    }

    public function test_team_photo_manager_path_guard_accepts_only_public_team_photo_directory(): void
    {
        $manager = app(TeamPhotoManager::class);

        $this->assertTrue($manager->isApprovedPath('team/photos/photo.jpg'));
        $this->assertFalse($manager->isApprovedPath('../team/photos/photo.jpg'));
        $this->assertFalse($manager->isApprovedPath('/team/photos/photo.jpg'));
        $this->assertFalse($manager->isApprovedPath('team/credentials/private.pdf'));
        $this->assertFalse($manager->isApprovedPath('team/photos/../private.jpg'));
    }

    private function member(array $overrides = []): TeamMember
    {
        return TeamMember::query()->create([
            'slug' => 'photo-proof',
            'name' => 'Photo Proof',
            'role' => 'Reviewer',
            'short_description' => 'Synthetic',
            'bio' => [],
            'expertise' => [],
            'photo_path' => null,
            'is_active' => true,
            'sort_order' => 1,
            ...$overrides,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Photo Proof',
            'slug' => 'photo-proof',
            'role' => 'Reviewer',
            'short_description' => 'Synthetic',
            'bio' => '',
            'expertise' => '',
            'is_active' => '1',
            'sort_order' => 1,
            ...$overrides,
        ];
    }

    private function png(): string
    {
        $image = imagecreatetruecolor(600, 750);
        imagefill($image, 0, 0, imagecolorallocate($image, 86, 102, 62));

        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    private function jpeg(): string
    {
        $image = imagecreatetruecolor(600, 750);
        imagefill($image, 0, 0, imagecolorallocate($image, 86, 102, 62));

        ob_start();
        imagejpeg($image);

        return (string) ob_get_clean();
    }

    private function webp(): string
    {
        $image = imagecreatetruecolor(600, 750);
        imagefill($image, 0, 0, imagecolorallocate($image, 86, 102, 62));

        ob_start();
        imagewebp($image);

        return (string) ob_get_clean();
    }
}
