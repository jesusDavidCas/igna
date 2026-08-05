<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use App\Support\Settings\BrandSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FaviconDeliveryTest extends TestCase
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

    public function test_public_favicon_route_serves_fallback_without_authentication(): void
    {
        $this->get(route('brand.favicon'))
            ->assertOk()
            ->assertHeader('content-type', 'image/x-icon');
    }

    public function test_superadministrator_can_upload_and_deliver_versioned_png_favicon(): void
    {
        Storage::fake('public');

        $before = app(BrandSettings::class)->faviconVersion();

        $this->actingAs($this->superAdmin)
            ->put(route('admin.settings.update'), [
                'settings' => $this->settingsPayload(),
                'brand_favicon' => UploadedFile::fake()->image('favicon.png', 64, 64),
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $path = Setting::query()->where('key', 'brand_favicon_path')->value('value');

        $this->assertIsString($path);
        $this->assertStringStartsWith('branding/', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertNotSame($before, app(BrandSettings::class)->faviconVersion());

        $this->get(route('brand.favicon', ['v' => app(BrandSettings::class)->faviconVersion()]))
            ->assertOk()
            ->assertHeader('content-type', 'image/png')
            ->assertHeader('x-content-type-options', 'nosniff');
    }

    public function test_invalid_favicon_payloads_are_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs($this->superAdmin)
            ->put(route('admin.settings.update'), [
                'settings' => $this->settingsPayload(),
                'brand_favicon' => UploadedFile::fake()->createWithContent('favicon.png', 'not a real image'),
            ])
            ->assertSessionHasErrors('brand_favicon');

        $this->actingAs($this->superAdmin)
            ->put(route('admin.settings.update'), [
                'settings' => $this->settingsPayload(),
                'brand_favicon' => UploadedFile::fake()->image('favicon.png', 140, 16),
            ])
            ->assertSessionHasErrors('brand_favicon');
    }

    public function test_settings_ui_uses_preview_and_hides_raw_favicon_path(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee(__('site.form_brand_favicon_current'))
            ->assertSee(__('site.form_brand_favicon_replace'))
            ->assertSee(__('site.form_brand_favicon_restore'))
            ->assertSee(route('brand.favicon'), false)
            ->assertDontSee('name="settings[brand_favicon_path]"', false)
            ->assertDontSee('name="settings[brand_logo_path]"', false);
    }

    public function test_home_login_and_admin_layouts_reference_resolved_favicon_url(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('brand.favicon'), false);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('brand.favicon'), false);

        $this->actingAs($this->superAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('brand.favicon'), false);
    }

    public function test_admin_without_superadmin_role_cannot_update_favicon(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'settings' => $this->settingsPayload(),
                'brand_favicon' => UploadedFile::fake()->image('favicon.png', 64, 64),
            ])
            ->assertForbidden();
    }

    public function test_untrusted_configured_favicon_path_falls_back_safely(): void
    {
        Setting::query()->where('key', 'brand_favicon_path')->update(['value' => '../private.env']);

        $this->get(route('brand.favicon'))
            ->assertOk()
            ->assertHeader('content-type', 'image/x-icon')
            ->assertDontSee('../private.env');
    }

    private function settingsPayload(): array
    {
        return [
            'company_name' => 'IGNA Studio',
            'support_email' => 'admin@ignastudio.com',
            'brand_logo_text' => 'IG',
            'storage_backend' => 'google_drive_stub',
        ];
    }
}
