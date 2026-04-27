<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'services' => Service::query()->count(),
                'open_tickets' => Ticket::query()->whereIn('status', ['new', 'in_progress'])->count(),
                'published_posts' => BlogPost::query()->where('status', 'published')->count(),
                'clients' => User::query()->where('role', 'client')->count(),
            ],
            'recentTickets' => Ticket::query()->with(['service', 'currentStage'])->latest()->limit(6)->get(),
        ]);
    }
}
