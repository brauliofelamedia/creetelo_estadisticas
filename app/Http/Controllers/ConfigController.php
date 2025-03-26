<?php

namespace App\Http\Controllers;

use App\Models\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConfigController extends Controller
{
    public function index()
    {

    }   
    
    public function edit()
    {
        $config = Config::first();
        return view('admin.config.edit', compact('config'));
    }

    public function update(Request $request)
    {
        $config = Config::first();
        $request->validate([
            'site_name' => 'required|string|max:255',
            'primary_color' => 'required|string|max:7',
            'secondary_color' => 'required|string|max:7',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'favicon' => 'nullable|image|mimes:ico,png|max:1024',
        ]);

        $config->site_name = $request->site_name;
        $config->primary_color = $request->primary_color;
        $config->secondary_color = $request->secondary_color;
        $config->save();

        return redirect()->back()->with('success', 'Se ha guardado la configuración');
    }

    public function media(Request $request)
    {
        $config = Config::first();
        $request->validate([
            'logo_light' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'logo_dark' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'favicon' => 'nullable|image|mimes:ico,png|max:1024'
        ]);

        if ($request->hasFile('logo_light')) {
            if ($config->logo_light) {
                Storage::delete($config->logo_light);
            }
            // Store the new file
            $config->logo_light = $request->file('logo_light')->store('config');
            $config->save();
        }

        if ($request->hasFile('logo_dark')) {
            if ($config->logo_dark) {
                Storage::delete($config->logo_dark);
            }
            $config->logo_dark = $request->file('logo_dark')->store('config');
            $config->save();
        }

        if ($request->hasFile('favicon')) {
            if ($config->favicon) {
                Storage::delete($config->favicon);
            }
            $config->favicon = $request->file('favicon')->store('config');
            $config->save();
        }

        return redirect()->back()->with('success', 'Se ha actualizado la imagen');
    }

    public function updateTags(Request $request)
    {
        $config = Config::first();
        $request->validate([
            'tags' => 'required|string'
        ]);
        $config->tags = $request->tags;
        $config->save();

        $files = [
            storage_path('app/transactions.json'),
            storage_path('app/contacts.json'),
            storage_path('app/subscriptions.json')
        ];

        foreach ($files as $filePath) {
            if (file_exists($filePath)) {
            unlink($filePath);
            }
        }
        return redirect()->back()->with('success', 'Se han actualizado las etiquetas y se ha descargado todos los archivos');
    }
}
