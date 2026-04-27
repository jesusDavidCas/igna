<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceStageRequest;
use App\Models\Service;
use App\Models\ServiceStage;
use Illuminate\Http\RedirectResponse;

class ServiceStageController extends Controller
{
    public function store(ServiceStageRequest $request, Service $service): RedirectResponse
    {
        $service->stages()->create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
            'is_client_visible' => $request->boolean('is_client_visible', true),
        ]);

        return redirect()->route('admin.services.edit', $service)->with('success', __('site.stage_created'));
    }

    public function update(ServiceStageRequest $request, Service $service, ServiceStage $stage): RedirectResponse
    {
        abort_unless($stage->service_id === $service->id, 404);

        $stage->update([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active'),
            'is_client_visible' => $request->boolean('is_client_visible'),
        ]);

        return redirect()->route('admin.services.edit', $service)->with('success', __('site.stage_updated'));
    }
}
