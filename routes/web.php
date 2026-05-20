<?php

use App\Enums\UserRole;
use App\Http\Controllers\Admin\BlogPostController as AdminBlogPostController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ProposalController as AdminProposalController;
use App\Http\Controllers\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Admin\ServiceStageController as AdminServiceStageController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\TeamCredentialController as AdminTeamCredentialController;
use App\Http\Controllers\Admin\TeamMemberController as AdminTeamMemberController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Client\PortalController;
use App\Http\Controllers\Client\TicketController as ClientTicketController;
use App\Http\Controllers\Public\BlogController;
use App\Http\Controllers\Public\LandingController;
use App\Http\Controllers\Public\ProposalController;
use App\Http\Controllers\Public\ServiceRequestController;
use App\Http\Controllers\Public\TeamCredentialController;
use App\Http\Controllers\Public\TicketTrackingController;
use App\Http\Controllers\TicketFileDownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');
Route::get('/team/{slug}', [LandingController::class, 'team'])->name('team.show');
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

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/proposals/{proposal}/view', [ProposalController::class, 'show'])
    ->middleware(['signed', 'throttle:30,1'])
    ->name('proposals.public.show');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:'.UserRole::SUPER_ADMIN->value.','.UserRole::ADMIN->value])
    ->group(function (): void {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::resource('services', AdminServiceController::class)->except(['show', 'destroy']);
        Route::post('/services/{service}/stages', [AdminServiceStageController::class, 'store'])->name('services.stages.store');
        Route::put('/services/{service}/stages/{stage}', [AdminServiceStageController::class, 'update'])->name('services.stages.update');

        Route::get('/tickets', [AdminTicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/{ticket}', [AdminTicketController::class, 'show'])->name('tickets.show');
        Route::put('/tickets/{ticket}/client', [AdminTicketController::class, 'updateClient'])->name('tickets.client.update');
        Route::put('/tickets/{ticket}/stage', [AdminTicketController::class, 'updateStage'])->name('tickets.stage.update');
        Route::put('/tickets/{ticket}/stage/back', [AdminTicketController::class, 'moveBack'])->name('tickets.stage.back');
        Route::put('/tickets/{ticket}/stages/{event}/complete', [AdminTicketController::class, 'completeStage'])->name('tickets.stages.complete');
        Route::put('/tickets/{ticket}/stages/{event}/reopen', [AdminTicketController::class, 'reopenStage'])->name('tickets.stages.reopen');
        Route::post('/tickets/{ticket}/files', [AdminTicketController::class, 'storeFile'])->name('tickets.files.store');
        Route::put('/tickets/{ticket}/files/{file}/visibility', [AdminTicketController::class, 'updateFileVisibility'])->name('tickets.files.visibility.update');
        Route::delete('/tickets/{ticket}/files/{file}', [AdminTicketController::class, 'destroyFile'])->name('tickets.files.destroy');
        Route::get('/tickets/{ticket}/files/{file}/download', [TicketFileDownloadController::class, 'admin'])->name('tickets.files.download');

        Route::resource('blog', AdminBlogPostController::class)->parameters(['blog' => 'post'])->except(['show']);
        Route::resource('team', AdminTeamMemberController::class)->parameters(['team' => 'teamMember'])->except(['show', 'destroy']);
        Route::post('/team/{teamMember}/credentials', [AdminTeamCredentialController::class, 'store'])->name('team.credentials.store');
        Route::delete('/team/{teamMember}/credentials/{credential}', [AdminTeamCredentialController::class, 'destroy'])->name('team.credentials.destroy');
        Route::get('/proposals/{proposal}/pdf', [AdminProposalController::class, 'pdf'])->name('proposals.pdf');
        Route::resource('proposals', AdminProposalController::class)->except(['destroy']);
        Route::post('/proposals/{proposal}/excel', [AdminProposalController::class, 'uploadExcel'])->name('proposals.excel.upload');

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
        Route::get('/tickets/{ticket}/files/{file}/download', [TicketFileDownloadController::class, 'client'])->name('tickets.files.download');
    });
