<?php

namespace App\Services;

use App\Enums\EvaluationType;
use App\Models\PeerEvaluation;
use App\Models\Project;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GradeExportService
{
    public function exportProjectCsv(Project $project): StreamedResponse
    {
        $project->load([
            'course',
            'groups.members',
            'rubric.criteria',
        ]);

        $filename = 'studentlink-'.str($project->title)->slug().'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($project) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Groupe',
                'Étudiant',
                'Email',
                'Type évaluation',
                'Cible',
                'Critère',
                'Score',
                'Max',
                'Poids %',
                'Évaluateur',
                'Date',
            ], ';');

            $evaluations = PeerEvaluation::query()
                ->where('project_id', $project->id)
                ->where('status', 'completed')
                ->with([
                    'reviewer',
                    'revieweeGroup',
                    'revieweeUser',
                    'scores.criterion',
                ])
                ->orderBy('id')
                ->get();

            foreach ($evaluations as $evaluation) {
                $this->writeRawScores($handle, $project, $evaluation);
            }

            $this->writeWeightedSummaries($handle, $project, $evaluations);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Note on a 100-point scale: each scored criterion contributes
     * (score / max) × weight. Weights of a saved rubric sum to 100,
     * so a fully scored evaluation at the maximum is 100.
     * A criterion with no score contributes 0.
     */
    private function weightedNote(PeerEvaluation $evaluation): ?float
    {
        $total = 0.0;
        $used = 0;

        foreach ($evaluation->scores as $score) {
            $criterion = $score->criterion;

            if ($criterion === null || $criterion->max_score < 1) {
                continue;
            }

            $total += ($score->score / $criterion->max_score) * $criterion->weight;
            $used++;
        }

        return $used === 0 ? null : $total;
    }

    private function formatNote(float $note): string
    {
        return number_format($note, 2, ',', '');
    }

    /**
     * @param  resource  $handle
     */
    private function writeRawScores($handle, Project $project, PeerEvaluation $evaluation): void
    {
        $target = $evaluation->type === EvaluationType::Inter
            ? $evaluation->revieweeGroup?->name
            : $evaluation->revieweeUser?->name;

        foreach ($evaluation->scores as $score) {
            if ($score->criterion === null) {
                continue;
            }

            fputcsv($handle, [
                $this->memberGroupName($project, $evaluation),
                $evaluation->revieweeUser?->name ?? '—',
                $evaluation->revieweeUser?->email ?? '—',
                $evaluation->type->label(),
                $target,
                $score->criterion->label,
                $score->score,
                $score->criterion->max_score,
                $score->criterion->weight,
                $evaluation->reviewer->name,
                $evaluation->submitted_at?->format('d/m/Y H:i'),
            ], ';');
        }
    }

    /**
     * @param  resource  $handle
     * @param  Collection<int, PeerEvaluation>  $evaluations
     */
    private function writeWeightedSummaries($handle, Project $project, Collection $evaluations): void
    {
        fwrite($handle, "\n");
        fputcsv($handle, ['Moyenne pondérée par étudiant (intra-groupe)'], ';');
        fputcsv($handle, [
            'Groupe',
            'Étudiant',
            'Email',
            'Note pondérée (/100)',
            'Nombre d\'évaluations',
        ], ';');

        $evaluations
            ->filter(fn (PeerEvaluation $evaluation) => $evaluation->type === EvaluationType::Intra
                && $evaluation->reviewee_user_id !== null)
            ->groupBy('reviewee_user_id')
            ->map(function (Collection $group) use ($project) {
                $notes = $this->notesFor($group);

                if ($notes->isEmpty()) {
                    return null;
                }

                $evaluation = $group->first();
                $student = $evaluation->revieweeUser;

                return [
                    $this->memberGroupName($project, $evaluation),
                    $student?->name ?? '—',
                    $student?->email ?? '—',
                    $this->formatNote((float) $notes->avg()),
                    $notes->count(),
                ];
            })
            ->filter()
            ->sortBy(fn (array $row) => $row[1])
            ->each(fn (array $row) => fputcsv($handle, $row, ';'));

        fwrite($handle, "\n");
        fputcsv($handle, ['Moyenne pondérée par groupe (inter-groupe)'], ';');
        fputcsv($handle, [
            'Groupe',
            'Note pondérée (/100)',
            'Nombre d\'évaluations',
        ], ';');

        $evaluations
            ->filter(fn (PeerEvaluation $evaluation) => $evaluation->type === EvaluationType::Inter
                && $evaluation->reviewee_group_id !== null)
            ->groupBy('reviewee_group_id')
            ->map(function (Collection $group) {
                $notes = $this->notesFor($group);

                if ($notes->isEmpty()) {
                    return null;
                }

                return [
                    $group->first()->revieweeGroup?->name ?? '—',
                    $this->formatNote((float) $notes->avg()),
                    $notes->count(),
                ];
            })
            ->filter()
            ->sortBy(fn (array $row) => $row[0])
            ->each(fn (array $row) => fputcsv($handle, $row, ';'));
    }

    /**
     * @param  Collection<int, PeerEvaluation>  $evaluations
     * @return Collection<int, float>
     */
    private function notesFor(Collection $evaluations): Collection
    {
        return $evaluations
            ->map(fn (PeerEvaluation $evaluation) => $this->weightedNote($evaluation))
            ->filter(fn (?float $note) => $note !== null)
            ->values();
    }

    private function memberGroupName(Project $project, PeerEvaluation $evaluation): string
    {
        if ($evaluation->type === EvaluationType::Inter) {
            return $evaluation->revieweeGroup?->name ?? '—';
        }

        foreach ($project->groups as $group) {
            if ($group->members->contains('id', $evaluation->reviewee_user_id)) {
                return $group->name;
            }
        }

        return '—';
    }
}
