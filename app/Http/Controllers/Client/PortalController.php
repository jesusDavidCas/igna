<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class PortalController extends Controller
{
    public function __invoke(): View
    {
        $user = request()->user();

        return view('client.dashboard', [
            'tickets' => $user->tickets()->with(['service', 'currentStage'])->latest()->get(),
        ]);
    }
}
