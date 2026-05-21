<?php

namespace Tests\Feature;

use App\Mail\ProjectUpdateMail;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\Ticket;
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

        $member = TeamMember::query()->firstOrFail();
        $path = UploadedFile::fake()->create('credential.pdf', 32, 'application/pdf')
            ->storeAs("team/credentials/{$member->slug}", 'credential.pdf', 'local');

        $credential = $member->credentials()->create([
            'title' => 'Professional diploma',
            'institution' => 'Universidad de prueba',
            'document_path' => $path,
            'original_name' => 'credential.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 32000,
            'preview_page_count' => 0,
            'is_public' => true,
            'sort_order' => 1,
        ]);

        $url = URL::temporarySignedRoute('team.credentials.show', now()->addMinutes(5), [
            'teamMember' => $member,
            'credential' => $credential,
        ]);

        $this->get($url)
            ->assertOk()
            ->assertSee('Professional diploma')
            ->assertSee(__('site.open_pdf_new_tab'))
            ->assertSee(__('site.protected_pdf_download_note'))
            ->assertSee('toolbar=1', false)
            ->assertSee('<object', false);
    }

    public function test_public_pdf_credential_file_route_returns_watermarked_derivative(): void
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

        $url = URL::temporarySignedRoute('team.credentials.file', now()->addMinutes(5), [
            'teamMember' => $member,
            'credential' => $credential,
        ]);

        $response = $this->get($url);

        $response
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename="credential-protected.pdf"');

        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString('IGNA STUDIO', $response->getContent());
        $this->assertStringContainsString('DOCUMENTO NO CONTROLADO', $response->getContent());
    }
}
