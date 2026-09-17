<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\StoreSettings;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit()
    {
        return view('admin.settings.edit', [
            'settings' => Setting::query()->orderBy('group')->orderBy('key')->get()->pluck('value', 'key'),
        ]);
    }

    public function update(Request $request)
    {
        foreach ($request->except('_token') as $key => $value) {
            StoreSettings::put($key, $value, str_contains($key, 'logo') || str_contains($key, 'color') || str_contains($key, 'favicon') ? 'branding' : 'general');
        }

        return back()->with('status', 'Settings updated.');
    }
}
