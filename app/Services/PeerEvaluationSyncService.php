<?php

namespace App\Services;

use App\Enums\EvaluationType;
use App\Enums\PeerEvaluationStatus;
use App\Models\PeerEvaluation;
use App\Models\Project;

class PeerEvaluationSyncService
{
    public function syncForProject(Project $project): void
    {
        $groups = $project->groups()->with('members')->get();

        if ($groups->count() < 1) {
            return;
        }

        foreach ($groups as $reviewerGroup) {
            foreach ($reviewerGroup->members as $reviewer) {
                $this->syncIntraEvaluations($project, $reviewerGroup, $reviewer);
                $this->syncInterEvaluations($project, $reviewerGroup, $reviewer, $groups);
            }
        }
    }

    public function syncForUserProjects(int $userId): void
    {
        $projectIds = \App\Models\Group::query()
            ->whereHas('members', fn ($q) => $q->where('users.id', $userId))
            ->pluck('project_id')
            ->unique();

        Project::query()
            ->whereIn('id', $projectIds)
            ->each(fn (Project $project) => $this->syncForProject($project));
    }

    private function syncIntraEvaluations(Project $project, $reviewerGroup, $reviewer): void
    {
        foreach ($reviewerGroup->members as $member) {
            if ($member->id === $reviewer->id) {
                continue;
            }

            PeerEvaluation::firstOrCreate(
                [
                    'project_id' => $project->id,
                    'reviewer_id' => $reviewer->id,
                    'type' => EvaluationType::Intra,
                    'reviewee_user_id' => $member->id,
                ],
                [
                    'status' => PeerEvaluationStatus::Pending,
                ],
            );
        }
    }

    private function syncInterEvaluations(Project $project, $reviewerGroup, $reviewer, $groups): void
    {
        foreach ($groups as $targetGroup) {
            if ($targetGroup->id === $reviewerGroup->id) {
                continue;
            }

            PeerEvaluation::firstOrCreate(
                [
                    'project_id' => $project->id,
                    'reviewer_id' => $reviewer->id,
                    'type' => EvaluationType::Inter,
                    'reviewee_group_id' => $targetGroup->id,
                ],
                [
                    'status' => PeerEvaluationStatus::Pending,
                ],
            );
        }
    }
}
