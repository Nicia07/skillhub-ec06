<?php

namespace App\Http\Controllers;

use App\Models\Formation;
use App\Models\Signalement;
use Illuminate\Http\Request;

class SignalementController extends Controller
{
    // Liste des signalements reçus pour une formation. Réservé au formateur
    // propriétaire de la formation. GET /api/formations/{id}/signalements
    public function index($idFormation)
    {
        $formation = Formation::find($idFormation);

        if (! $formation) {
            return response()->json(['message' => 'Formation introuvable'], 404);
        }

        if ($formation->user_id !== auth()->user()->id) {
            return response()->json(['message' => 'Action non autorisée'], 403);
        }

        $signalements = Signalement::where('formation_id', $idFormation)->latest()->get();

        return response()->json($signalements);
    }

    // Signaler un problème sur une formation. Route protégée (JWT requis) :
    // le formation_id vient de l'URL, le user_id de l'utilisateur authentifié.
    public function store(Request $request, $idFormation)
    {
        if (! Formation::where('id', $idFormation)->exists()) {
            return response()->json(['message' => 'Formation introuvable'], 404);
        }

        $validated = $request->validate([
            'motif' => 'required|in:contenu_inapproprie,erreur_technique,autre',
            'description' => 'nullable|string',
        ]);

        $dejaSignale = Signalement::where('formation_id', $idFormation)
            ->where('user_id', auth()->user()->id)
            ->exists();

        if ($dejaSignale) {
            return response()->json(['message' => 'Vous avez déjà signalé cette formation'], 422);
        }

        $signalement = Signalement::create([
            'formation_id' => $idFormation,
            'user_id' => auth()->user()->id,
            'motif' => $validated['motif'],
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json(['message' => 'Signalement envoyé avec succès', 'signalement' => $signalement], 201);
    }
}
