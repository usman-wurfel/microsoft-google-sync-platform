<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::getCurrent();
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'platform_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'version' => 'required|string|max:50',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'favicon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,ico|max:1024',
        ]);

        $settings = Setting::getCurrent();

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($settings->logo_path && file_exists(public_path($settings->logo_path))) {
                unlink(public_path($settings->logo_path));
            }
            
            $logoFile = $request->file('logo');
            $logoName = 'logo-' . time() . '.' . $logoFile->getClientOriginalExtension();
            $logoPath = 'assets/images/' . $logoName;
            
            // Create directory if it doesn't exist
            if (!file_exists(public_path('assets/images'))) {
                mkdir(public_path('assets/images'), 0755, true);
            }
            
            $logoFile->move(public_path('assets/images'), $logoName);
            $settings->logo_path = $logoPath;
        }

        // Handle favicon upload
        if ($request->hasFile('favicon')) {
            // Delete old favicon if exists
            if ($settings->favicon_path && file_exists(public_path($settings->favicon_path))) {
                unlink(public_path($settings->favicon_path));
            }
            
            $faviconFile = $request->file('favicon');
            $faviconName = 'favicon-' . time() . '.' . $faviconFile->getClientOriginalExtension();
            $faviconPath = 'assets/images/' . $faviconName;
            
            // Create directory if it doesn't exist
            if (!file_exists(public_path('assets/images'))) {
                mkdir(public_path('assets/images'), 0755, true);
            }
            
            $faviconFile->move(public_path('assets/images'), $faviconName);
            $settings->favicon_path = $faviconPath;
        }

        // Update other fields
        $settings->platform_name = $request->platform_name;
        $settings->description = $request->description;
        $settings->version = $request->version;
        $settings->save();

        return redirect()->route('settings.index')->with('success', 'Settings updated successfully!');
    }
}
