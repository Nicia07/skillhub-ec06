<?php

namespace App\Console\Commands;

use App\Models\Inscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DesinscriptionInactiviteCommand extends Command
{
    protected $signature = 'app:desinscription-inactivite';

    protected $description = 'Desinscrit automatiquement les apprenants inactifs depuis plus de 30 jours de leurs formations en cours';

    public function handle(): int
    {
        $seuil = now()->subDays(30);

        $inscriptions = Inscription::where('status', 'en cours')
            ->where(function ($query) use ($seuil) {
                $query->whereNull('last_activity_at')
                    ->orWhere('last_activity_at', '<', $seuil);
            })
            ->get();

        $count = $inscriptions->count();

        foreach ($inscriptions as $inscription) {
            $inscription->delete();
        }

        $message = "{$count} desinscription(s) effectuee(s) pour inactivite (> 30 jours).";
        $this->info($message);
        Log::info('[desinscription-inactivite] '.$message);

        return self::SUCCESS;
    }
}
