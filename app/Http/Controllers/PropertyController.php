<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PropertyController extends Controller
{
    public function downloadReglement(Property $property)
    {
        if (! $property->reglement_fichier) {
            abort(404);
        }

        $path = storage_path('app/public/' . $property->reglement_fichier);
        if (! file_exists($path)) {
            abort(404);
        }

        return response()->download($path);
    }

    public function showUploadReglementForm(Property $property)
    {
        $user = Auth::user();
        if ($user->role !== 'syndic') {
            abort(403);
        }

        if ($user->syndic?->property_id !== $property->id) {
            abort(403);
        }

        return view('properties.reglement-upload', compact('property'));
    }

    public function uploadReglement(Request $request, Property $property)
    {
        $user = Auth::user();
        if ($user->role !== 'syndic') {
            abort(403);
        }

        if ($user->syndic?->property_id !== $property->id) {
            abort(403);
        }

        $data = $request->validate([
            'reglement' => 'required|file',
        ]);

        $path = $request->file('reglement')->store('reglements', 'public');

        $property->reglement_fichier = $path;
        $property->save();

        return redirect()->back();
    }
}
