<?php

namespace Tests\Feature;

use App\Models\TeamMember;
use App\Services\Credentials\CredentialPreviewRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use setasign\Fpdi\Fpdi;
use Tests\TestCase;

class TeamCredentialProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed();
    }

    public function test_uploaded_image_credential_becomes_private_rasterized_protected_pdf(): void
    {
        Storage::fake('local');

        $member = TeamMember::query()->firstOrFail();

        $this->actingAs(\App\Models\User::query()->where('email', 'admin@ignastudio.com')->firstOrFail())
            ->post(route('admin.team.credentials.store', $member), [
                'title' => 'Synthetic image credential',
                'institution' => 'Synthetic institute',
                'document' => UploadedFile::fake()->image('credential.png', 900, 1200),
                'is_public' => '1',
                'sort_order' => 1,
            ])
            ->assertRedirect(route('admin.team.edit', $member));

        $credential = $member->credentials()->where('title', 'Synthetic image credential')->firstOrFail();

        Storage::disk('local')->assertExists($credential->document_path);
        Storage::disk('local')->assertExists($credential->protected_document_path);
        Storage::disk('local')->assertMissing('public/'.$credential->document_path);
        $this->assertSame('ready', $credential->protection_status);
        $this->assertStringEndsWith('.pdf', $credential->protected_document_path);
        $this->assertNotSame($credential->original_checksum, $credential->protected_checksum);
    }

    public function test_uploaded_pdf_is_rasterized_page_by_page_without_clean_text_layer(): void
    {
        Storage::fake('local');

        $member = TeamMember::query()->firstOrFail();
        $sourcePdf = $this->syntheticTwoPagePdf();
        $path = "team/credentials/{$member->slug}/two-page.pdf";
        Storage::disk('local')->put($path, $sourcePdf);

        $credential = $member->credentials()->create([
            'title' => 'Synthetic multi-page credential',
            'institution' => 'Synthetic institute',
            'document_path' => $path,
            'original_name' => 'two-page.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => Storage::disk('local')->size($path),
            'preview_page_count' => 2,
            'is_public' => true,
            'sort_order' => 1,
        ]);

        $credential = app(CredentialPreviewRenderer::class)->generateProtectedDerivative($credential);

        Storage::disk('local')->assertExists($credential->protected_document_path);
        $protectedPath = Storage::disk('local')->path($credential->protected_document_path);

        $pdf = new Fpdi;
        $this->assertSame(2, $pdf->setSourceFile($protectedPath));
        $this->assertStringNotContainsString('FIRST CLEAN TEXT LAYER', Storage::disk('local')->get($credential->protected_document_path));
        $this->assertStringNotContainsString('SECOND CLEAN TEXT LAYER', Storage::disk('local')->get($credential->protected_document_path));
        $this->assertSame(2, $credential->preview_page_count);
        $this->assertGreaterThan(5000, Storage::disk('local')->size($credential->protected_document_path));
    }

    public function test_pdf_rasterization_uses_ghostscript_fallback_when_poppler_is_unavailable(): void
    {
        Storage::fake('local');

        config([
            'services.poppler.enabled' => false,
            'services.ghostscript.gs' => $this->fakeGhostscriptExecutable(),
        ]);

        $member = TeamMember::query()->firstOrFail();
        $sourcePdf = $this->syntheticTwoPagePdf();
        $path = "team/credentials/{$member->slug}/ghostscript-two-page.pdf";
        Storage::disk('local')->put($path, $sourcePdf);

        $credential = $member->credentials()->create([
            'title' => 'Ghostscript fallback credential',
            'institution' => 'Synthetic institute',
            'document_path' => $path,
            'original_name' => 'ghostscript-two-page.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => Storage::disk('local')->size($path),
            'preview_page_count' => 2,
            'is_public' => true,
            'sort_order' => 1,
        ]);

        $credential = app(CredentialPreviewRenderer::class)->generateProtectedDerivative($credential);

        Storage::disk('local')->assertExists($credential->protected_document_path);
        $protectedPath = Storage::disk('local')->path($credential->protected_document_path);

        $pdf = new Fpdi;
        $this->assertSame(2, $pdf->setSourceFile($protectedPath));
        $this->assertSame('ready', $credential->protection_status);
        $this->assertNull($credential->protection_error);
        $this->assertStringNotContainsString('FIRST CLEAN TEXT LAYER', Storage::disk('local')->get($credential->protected_document_path));
        $this->assertStringNotContainsString('SECOND CLEAN TEXT LAYER', Storage::disk('local')->get($credential->protected_document_path));
    }

    public function test_failed_regeneration_preserves_existing_valid_protected_derivative(): void
    {
        Storage::fake('local');

        $member = TeamMember::query()->firstOrFail();
        $path = UploadedFile::fake()->image('credential.jpg', 900, 1200)
            ->storeAs("team/credentials/{$member->slug}", 'credential.jpg', 'local');

        $credential = $member->credentials()->create([
            'title' => 'Regeneration credential',
            'institution' => 'Synthetic institute',
            'document_path' => $path,
            'original_name' => 'credential.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => Storage::disk('local')->size($path),
            'preview_page_count' => 1,
            'is_public' => true,
            'sort_order' => 1,
        ]);

        $credential = app(CredentialPreviewRenderer::class)->generateProtectedDerivative($credential);
        $oldProtectedPath = $credential->protected_document_path;
        Storage::disk('local')->delete($credential->document_path);

        $credential = app(CredentialPreviewRenderer::class)->generateProtectedDerivative($credential);

        $this->assertSame('ready', $credential->protection_status);
        $this->assertSame($oldProtectedPath, $credential->protected_document_path);
        $this->assertNotNull($credential->protection_error);
        Storage::disk('local')->assertExists($oldProtectedPath);
    }

    public function test_authorized_view_returns_inline_protected_pdf_without_storage_path(): void
    {
        Storage::fake('local');

        $member = TeamMember::query()->firstOrFail();
        $path = UploadedFile::fake()->image('credential.jpg', 900, 1200)
            ->storeAs("team/credentials/{$member->slug}", 'credential.jpg', 'local');

        $credential = $member->credentials()->create([
            'title' => 'Download credential',
            'institution' => 'Synthetic institute',
            'document_path' => $path,
            'original_name' => 'credential.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => Storage::disk('local')->size($path),
            'preview_page_count' => 1,
            'is_public' => true,
            'sort_order' => 1,
        ]);
        $credential = app(CredentialPreviewRenderer::class)->generateProtectedDerivative($credential);

        $url = URL::temporarySignedRoute('team.credentials.file', now()->addMinutes(5), [$member, $credential]);

        $response = $this->get($url)
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename="credential-protected.pdf"')
            ->assertHeader('x-content-type-options', 'nosniff');

        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringNotContainsString($credential->document_path, $response->getContent());
        $this->assertStringNotContainsString($credential->protected_document_path, $response->getContent());
    }

    private function syntheticTwoPagePdf(): string
    {
        $pdf = new \FPDF;
        $pdf->AddPage('P');
        $pdf->SetFont('Helvetica', 'B', 18);
        $pdf->Cell(0, 10, 'FIRST CLEAN TEXT LAYER');
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->Ln(20);
        $pdf->Cell(0, 10, 'Small text, seal, and signature sample');
        $pdf->AddPage('L');
        $pdf->SetFont('Helvetica', 'B', 18);
        $pdf->Cell(0, 10, 'SECOND CLEAN TEXT LAYER');
        $pdf->SetDrawColor(40, 80, 120);
        $pdf->Rect(30, 40, 56, 36);
        $pdf->Line(32, 72, 84, 44);

        return $pdf->Output('S');
    }

    private function fakeGhostscriptExecutable(): string
    {
        $directory = storage_path('framework/testing/ghostscript-fallback-'.uniqid());

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $pngPath = $directory.'/source-page.png';
        $image = imagecreatetruecolor(140, 200);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        imagestring($image, 5, 16, 88, 'GS PAGE', imagecolorallocate($image, 30, 60, 90));
        imagepng($image, $pngPath);
        imagedestroy($image);

        $scriptPath = $directory.'/gs';
        $script = <<<'PHP'
#!/usr/bin/env php
<?php

$source = SOURCE_PNG;
$outputPattern = null;
$firstPage = 1;
$lastPage = 2;

foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '-sOutputFile=')) {
        $outputPattern = substr($argument, strlen('-sOutputFile='));
    }

    if (str_starts_with($argument, '-dFirstPage=')) {
        $firstPage = max(1, (int) substr($argument, strlen('-dFirstPage=')));
    }

    if (str_starts_with($argument, '-dLastPage=')) {
        $lastPage = max($firstPage, (int) substr($argument, strlen('-dLastPage=')));
    }
}

if ($outputPattern === null) {
    fwrite(STDERR, 'missing output pattern');
    exit(1);
}

for ($page = $firstPage; $page <= $lastPage; $page++) {
    $target = str_contains($outputPattern, '%')
        ? sprintf($outputPattern, $page)
        : $outputPattern;

    copy($source, $target);
}

exit(0);
PHP;

        $script = str_replace('SOURCE_PNG', var_export($pngPath, true), $script);
        file_put_contents($scriptPath, $script);
        chmod($scriptPath, 0755);

        return $scriptPath;
    }
}
