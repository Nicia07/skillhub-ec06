<?php

namespace App\Http\Controllers;

use App\Models\Inscription;
use Illuminate\Http\Request;

class InscriptionController extends Controller
{
    private function withFormation($inscriptions)
    {
        return $inscriptions->map(function ($inscription) {
            if ($inscription->formation) {
                $inscription->formation->pseudo_formateur = $inscription->formation->formateur->pseudo ?? null;
                $inscription->formation->unsetRelation('formateur');
            }

            return $inscription;
        });
    }

    // Formations suivies / terminées de l'apprenant connecté.
    public function index(Request $request)
    {
        $query = Inscription::with(['formation.formateur:id,pseudo'])
            ->where('id_apprenant', auth()->id());

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $inscriptions = $query->latest()->get();

        return response()->json($this->withFormation($inscriptions));
    }

    // Suivre une formation.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_formation' => 'required|integer|exists:formations,id',
        ]);

        $exists = Inscription::where('id_apprenant', auth()->id())
            ->where('id_formation', $validated['id_formation'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Vous suivez déjà cette formation'], 409);
        }

        $inscription = Inscription::create([
            'id_apprenant' => auth()->id(),
            'id_formation' => $validated['id_formation'],
            'status' => 'en cours',
        ]);

        $inscription->load('formation.formateur:id,pseudo');

        return response()->json(['message' => 'Formation suivie avec succès', 'inscription' => $inscription], 201);
    }

    // Marquer une formation suivie comme terminée.
    public function terminer($id)
    {
        $inscription = Inscription::find($id);

        if (! $inscription) {
            return response()->json(['message' => 'Inscription introuvable'], 404);
        }

        if ($inscription->id_apprenant !== auth()->id()) {
            return response()->json(['message' => 'Action non autorisée'], 403);
        }

        if ($inscription->status === 'terminée') {
            return response()->json(['message' => 'Cette formation est déjà terminée'], 422);
        }

        $inscription->update(['status' => 'terminée']);

        return response()->json(['message' => 'Formation marquée comme terminée', 'inscription' => $inscription]);
    }

    // Se désinscrire d'une formation en cours.
    public function destroy($id)
    {
        $inscription = Inscription::find($id);

        if (! $inscription) {
            return response()->json(['message' => 'Inscription introuvable'], 404);
        }

        if ($inscription->id_apprenant !== auth()->id()) {
            return response()->json(['message' => 'Action non autorisée'], 403);
        }

        if ($inscription->status === 'terminée') {
            return response()->json(['message' => 'Impossible de se désinscrire d\'une formation déjà terminée'], 422);
        }

        $inscription->delete();

        return response()->json(['message' => 'Désinscription réussie']);
    }
}
