<?php

namespace App\Http\Controllers\Ateliers\Atelier;

use App\Http\Controllers\Controller;
use App\Support\Photo;
use Illuminate\Http\Request;

class ProfilController extends Controller
{
    public function edit(Request $request)
    {
        return view('back.ateliers.profil', ['atelier' => $request->user()->atelier ?? abort(403)]);
    }

    public function update(Request $request)
    {
        $atelier = $request->user()->atelier ?? abort(403);

        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'specialite' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'code_postal' => ['nullable', 'string', 'max:10'],
            'ville' => ['required', 'string', 'max:100'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'horaires' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('logo')) {
            Photo::delete($atelier->logo);
            $data['logo'] = Photo::store($request->file('logo'), 'logos');
        } else {
            unset($data['logo']);
        }

        $atelier->update($data);

        return redirect()->route('atelier.profil.edit')->with('success', 'Votre profil a été mis à jour.');
    }
}
