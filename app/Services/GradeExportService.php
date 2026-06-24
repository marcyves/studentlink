<?php

namespace App\Services;

use App\Enums\EvaluationType;
use App\Models\PeerEvaluation;
use App\Models\Project;
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
                $target = $evaluation->type === EvaluationType::Inter
                    ? $evaluation->revieweeGroup?->name
                    : $evaluation->revieweeUser?->name;

                foreach ($evaluation->scores as $score) {
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

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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
