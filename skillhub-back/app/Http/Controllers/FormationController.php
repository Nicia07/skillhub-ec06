<?php

namespace App\Http\Controllers;

use App\Models\Formation;
use Illuminate\Http\Request;

class FormationController extends Controller
{
    private function withPseudoFormateur($formations)
    {
        return $formations->map(function ($formation) {
            $formation->pseudo_formateur = $formation->formateur->pseudo ?? null;
            $formation->unsetRelation('formateur');

            return $formation;
        });
    }

    // Catalogue public : toutes les formations, visible sans connexion.
    public function index()
    {
        $formations = Formation::with('formateur:id,pseudo')->latest()->get();

        return response()->json($this->withPseudoFormateur($formations));
    }

    // Détail public d'une formation. GET /api/formations/{id}
    public function show($id)
    {
        $formation = Formation::with('formateur:id,pseudo')->find($id);

        if (! $formation) {
            return response()->json(['message' => 'Formation introuvable'], 404);
        }

        $formation->pseudo_formateur = $formation->formateur->pseudo ?? null;
        $formation->unsetRelation('formateur');

        return response()->json($formation);
    }

    // Formations du formateur connecté (tableau de bord privé). GET /api/my-formations
    public function myFormations()
    {
        $user = auth()->user();

        $formations = Formation::with('formateur:id,pseudo')
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return response()->json($this->withPseudoFormateur($formations), 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1',
            'level' => 'required|in:beginner,intermediate,advanced',
            'categorie' => 'nullable|string|max:100',
            'ville' => 'nullable|string|max:100',
        ]);

        $validated['user_id'] = auth()->id();
        $formation = Formation::create($validated);

        return response()->json(['message' => 'Formation créée avec succès', 'formation' => $formation], 201);
    }

    public function update(Request $request, $id)
    {
        $formation = Formation::find($id);

        if (! $formation) {
            return response()->json(['message' => 'Formation introuvable'], 404);
        }

        if ($formation->user_id !== auth()->id()) {
            return response()->json(['message' => 'Action non autorisée'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1',
            'level' => 'required|in:beginner,intermediate,advanced',
            'categorie' => 'nullable|string|max:100',
            'ville' => 'nullable|string|max:100',
        ]);

        $formation->update($validated);

        return response()->json(['message' => 'Formation mise à jour', 'formation' => $formation], 200);
    }

    public function destroy($id)
    {
        $formation = Formation::find($id);

        if (! $formation) {
            return response()->json(['message' => 'Formation introuvable'], 404);
        }

        if ($formation->user_id !== auth()->id()) {
            return response()->json(['message' => 'Action non autorisée'], 403);
        }

        $formation->delete();

        return response()->json(['message' => 'Supprimé avec succès'], 200);
    }
}
