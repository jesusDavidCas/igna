<?php

use App\Enums\UserRole;
use App\Http\Controllers\Admin\BlogPostController as AdminBlogPostController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ProposalController as AdminProposalController;
use App\Http\Controllers\Admin\ProposalServiceTemplateController as AdminProposalServiceTemplateController;
use App\Http\Controllers\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Admin\ServiceStageController as AdminServiceStageController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\TeamCredentialController as AdminTeamCredentialController;
use App\Http\Controllers\Admin\TeamMemberController as AdminTeamMemberController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Client\PortalController;
use App\Http\Controllers\Client\TicketController as ClientTicketController;
use App\Http\Controllers\Public\BlogController;
use App\Http\Controllers\Public\LandingController;
use App\Http\Controllers\Public\ProposalController;
use App\Http\Controllers\Public\SeoResourceController;
use App\Http\Controllers\Public\ServiceRequestController;
use App\Http\Controllers\Public\TeamCredentialController;
use App\Http\Controllers\Public\TicketTrackingController;
use App\Http\Controllers\TicketClientDocumentController;
use App\Http\Controllers\TicketFileDownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');
Route::get('/team/{slug}', [LandingController::class, 'team'])->name('team.show');
Route::get('/sitemap.xml', [SeoResourceController::class, 'sitemap'])->name('seo.sitemap');
Route::get('/robots.txt', [SeoResourceController::class, 'robots'])->name('seo.robots');
Route::get('/llms.txt', [SeoResourceController::class, 'llms'])->name('seo.llms');
Route::get('/LLMMS.pub.txt', [SeoResourceController::class, 'llmsAlias'])->name('seo.llms.alias');
Route::get('/markdown/{page}.md', [SeoResourceController::class, 'markdown'])->name('seo.markdown.page');
Route::get('/markdown/team/{slug}.md', [SeoResourceController::class, 'teamMarkdown'])->name('seo.markdown.team');
Route::get('/markdown/blog/{post:slug}.md', [SeoResourceController::class, 'blogMarkdown'])->name('seo.markdown.blog');

// Credentials viewing and rendering routes are secured via:
// 1. Signed URLs (URL::signedRoute / URL::temporarySignedRoute) to prevent ID enumeration.
// 2. Strict request throttling to block brute-force scraping attempts.
Route::get('/team/{teamMember:slug}/credentials/{credential}/view', [TeamCredentialController::class, 'show'])
    ->middleware(['signed', 'throttle:20,1'])
    ->name('team.credentials.show');
Route::get('/team/{teamMember:slug}/credentials/{credential}/pages/{page}', [TeamCredentialController::class, 'previewPage'])
    ->middleware(['signed', 'throttle:30,1'])
    ->name('team.credentials.preview');
Route::get('/team/{teamMember:slug}/credentials/{credential}/file', [TeamCredentialController::class, 'file'])
    ->middleware(['signed', 'throttle:20,1'])
    ->name('team.credentials.file');
Route::post('/locale/{locale}', [LandingController::class, 'locale'])->name('locale.switch');

Route::post('/request', [ServiceRequestController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('requests.store');

Route::get('/tracking', [TicketTrackingController::class, 'index'])->name('tracking.index');
Route::post('/tracking', [TicketTrackingController::class, 'show'])
    ->middleware('throttle:20,1')
    ->name('tracking.show');
Route::get('/tracking/tickets/{ticket}/files/{file}', [TicketFileDownloadController::class, 'tracking'])
    ->middleware('signed')
    ->name('tracking.files.download');
Route::post('/tracking/tickets/{ticket}/documents', [TicketClientDocumentController::class, 'tracking'])
    ->middleware(['signed', 'throttle:ticket-document-upload'])
    ->name('tracking.documents.store');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');

// Public proposal routing handles two types of client access:
// 1. Token-based URL using a non-predictable 40-character string (similar to Google Docs links).
// 2. Signed URL using temporary HMAC signatures for secure proposal preview verification.
Route::get('/proposals/public/{publicToken}', [ProposalController::class, 'showByToken'])
    ->middleware('throttle:30,1')
    ->name('proposals.public.token.show');
Route::get('/proposals/{proposal}/view', [ProposalController::class, 'show'])
    ->middleware(['signed', 'throttle:30,1'])
    ->name('proposals.public.show');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:3,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.update');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Admin area access is strictly locked to Super Admin and Admin roles.
// Users must be authenticated, active, and matching the requested role enum values.
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:'.UserRole::SUPER_ADMIN->value.','.UserRole::ADMIN->value])
    ->group(function (): void {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::resource('services', AdminServiceController::class)->except(['show', 'destroy']);
        Route::match(['post', 'put'], '/services/{service}/translate', [AdminServiceController::class, 'translate'])->name('services.translate');
        Route::post('/services/{service}/stages', [AdminServiceStageController::class, 'store'])->name('services.stages.store');
        Route::put('/services/{service}/stages/{stage}', [AdminServiceStageController::class, 'update'])->name('services.stages.update');
        Route::match(['post', 'put'], '/services/{service}/stages/{stage}/translate', [AdminServiceStageController::class, 'translate'])->name('services.stages.translate');
        Route::delete('/services/{service}/stages/{stage}', [AdminServiceStageController::class, 'destroy'])->name('services.stages.destroy');

        Route::get('/tickets', [AdminTicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/{ticket}', [AdminTicketController::class, 'show'])->name('tickets.show');
        Route::put('/tickets/{ticket}/client', [AdminTicketController::class, 'updateClient'])->name('tickets.client.update');
        Route::put('/tickets/{ticket}/stage', [AdminTicketController::class, 'updateStage'])->name('tickets.stage.update');
        Route::put('/tickets/{ticket}/stage/back', [AdminTicketController::class, 'moveBack'])->name('tickets.stage.back');
        Route::put('/tickets/{ticket}/stages/{event}/complete', [AdminTicketController::class, 'completeStage'])->name('tickets.stages.complete');
        Route::put('/tickets/{ticket}/stages/{event}/reopen', [AdminTicketController::class, 'reopenStage'])->name('tickets.stages.reopen');
        Route::post('/tickets/{ticket}/files', [AdminTicketController::class, 'storeFile'])->name('tickets.files.store');
        Route::put('/tickets/{ticket}/files/{file}/visibility', [AdminTicketController::class, 'updateFileVisibility'])->name('tickets.files.visibility.update');
        Route::patch('/tickets/{ticket}/files/{file}/review', [AdminTicketController::class, 'markFileReviewed'])->name('tickets.files.review.update');
        Route::patch('/tickets/{ticket}/files/{file}/reject', [AdminTicketController::class, 'rejectFile'])->name('tickets.files.reject.update');
        Route::delete('/tickets/{ticket}/files/{file}', [AdminTicketController::class, 'destroyFile'])->name('tickets.files.destroy');
        Route::get('/tickets/{ticket}/files/{file}/download', [TicketFileDownloadController::class, 'admin'])->name('tickets.files.download');

        Route::resource('blog', AdminBlogPostController::class)->parameters(['blog' => 'post'])->except(['show']);
        Route::resource('team', AdminTeamMemberController::class)->parameters(['team' => 'teamMember'])->except(['show', 'destroy']);
        Route::post('/team/{teamMember}/credentials', [AdminTeamCredentialController::class, 'store'])->name('team.credentials.store');
        Route::post('/team/{teamMember}/credentials/{credential}/regenerate', [AdminTeamCredentialController::class, 'regenerate'])->name('team.credentials.regenerate');
        Route::delete('/team/{teamMember}/credentials/{credential}', [AdminTeamCredentialController::class, 'destroy'])->name('team.credentials.destroy');
        Route::post('/proposal-templates/{proposalTemplate}/duplicate', [AdminProposalServiceTemplateController::class, 'duplicate'])->name('proposal-templates.duplicate');
        Route::resource('proposal-templates', AdminProposalServiceTemplateController::class)->parameters(['proposal-templates' => 'proposalTemplate'])->except(['show']);
        Route::get('/proposals/{proposal}/pdf', [AdminProposalController::class, 'pdf'])->name('proposals.pdf');
        Route::resource('proposals', AdminProposalController::class)->except(['destroy']);

        Route::middleware('role:'.UserRole::SUPER_ADMIN->value)->group(function (): void {
            Route::put('/users/{user}/password', [AdminUserController::class, 'updatePassword'])->name('users.password.update');
            Route::resource('users', AdminUserController::class)->except(['show', 'destroy']);
            Route::get('/settings', [AdminSettingController::class, 'edit'])->name('settings.edit');
            Route::put('/settings', [AdminSettingController::class, 'update'])->name('settings.update');
        });
    });

Route::prefix('portal')
    ->name('client.')
    ->middleware(['auth', 'role:'.UserRole::CLIENT->value])
    ->group(function (): void {
        Route::get('/', PortalController::class)->name('dashboard');
        Route::get('/tickets/{ticket}', [ClientTicketController::class, 'show'])->name('tickets.show');
        Route::post('/tickets/{ticket}/documents', [TicketClientDocumentController::class, 'client'])->name('tickets.documents.store');
        Route::get('/tickets/{ticket}/files/{file}/download', [TicketFileDownloadController::class, 'client'])->name('tickets.files.download');
    });
