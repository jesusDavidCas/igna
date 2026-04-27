<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingUpdateRequest;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'settings' => Setting::query()->orderBy('group')->orderBy('key')->get()->groupBy('group'),
        ]);
    }

    public function update(SettingUpdateRequest $request): RedirectResponse
    {
        foreach ($request->validated('settings') as $key => $value) {
            Setting::query()
                ->where('key', $key)
                ->update(['value' => $value]);
        }

        if ($request->hasFile('brand_logo')) {
            $this->storeBrandingFile('brand_logo_path', $request->file('brand_logo')->store('branding', 'public'));
        }

        if ($request->hasFile('brand_favicon')) {
            $this->storeBrandingFile('brand_favicon_path', $request->file('brand_favicon')->store('branding', 'public'));
        }

        return redirect()->route('admin.settings.edit')->with('success', __('site.settings_updated'));
    }

    private function storeBrandingFile(string $key, string $path): void
    {
        $setting = Setting::query()->where('key', $key)->first();

        if ($setting?->value) {
            Storage::disk('public')->delete($setting->value);
        }

        Setting::query()
            ->where('key', $key)
            ->update(['value' => $path]);
    }
}
