<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\TaskOverdueNotification;
use Illuminate\Console\Command;

class RemindOverdueTasks extends Command
{
    protected $signature = 'taches:rappel-retard {--intervalle=3 : Nombre de jours minimum entre deux rappels pour une même tâche}';

    protected $description = 'Prévient les personnes concernées des tâches dont l\'échéance est dépassée';

    public function handle(): int
    {
        $intervalle = (int) $this->option('intervalle');
        $envoyes = 0;

        $taches = Task::with(['project.groupe.chef', 'project.groupe.membres', 'project.stage.stagiaire', 'project.mentor', 'assigne'])
            ->whereNotNull('date_echeance')
            ->whereDate('date_echeance', '<', now()->toDateString())
            ->where('statut', '!=', 'termine')
            ->get();

        foreach ($taches as $tache) {
            // Le mail est réservé à la personne à qui la tâche est confiée ; chef et mentor sont prévenus dans l'application.
            $responsables = $tache->assigne ? collect([$tache->assigne]) : $tache->project->stagiaires();

            foreach ($tache->participants() as $destinataire) {
                if ($this->dejaRappele($destinataire, $tache, $intervalle)) {
                    continue;
                }

                $destinataire->notify(new TaskOverdueNotification($tache, $responsables->contains('id', $destinataire->id)));
                $envoyes++;
            }
        }

        $this->info("Rappels de tâches en retard envoyés : {$envoyes}.");

        return self::SUCCESS;
    }

    /** Évite de relancer chaque jour : un rappel par tâche et par personne tous les N jours. */
    private function dejaRappele($destinataire, Task $tache, int $intervalle): bool
    {
        return $destinataire->notifications()
            ->where('type', TaskOverdueNotification::class)
            ->where('data->task_id', $tache->id)
            ->where('created_at', '>=', now()->subDays($intervalle))
            ->exists();
    }
}
