<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Group;
use App\Models\Project;
use App\Models\Rubric;
use App\Models\User;
use App\Services\PeerEvaluationSyncService;
use Illuminate\Database\Seeder;

class StudentLinkSeeder extends Seeder
{
    public function run(): void
    {
        $professor = User::factory()->professor()->create([
            'name' => 'Prof. Martin',
            'email' => 'prof@studentlink.test',
        ]);

        $students = collect([
            User::factory()->create(['name' => 'Alice Dupont', 'email' => 'alice@studentlink.test']),
            User::factory()->create(['name' => 'Bob Martin', 'email' => 'bob@studentlink.test']),
            User::factory()->create(['name' => 'Claire Leroy', 'email' => 'claire@studentlink.test']),
            User::factory()->create(['name' => 'David Chen', 'email' => 'david@studentlink.test']),
            User::factory()->create(['name' => 'Emma Rossi', 'email' => 'emma@studentlink.test']),
        ]);

        $course = Course::create([
            'professor_id' => $professor->id,
            'title' => 'Management de projet digital',
            'code' => 'MPWD-2026',
            'join_code' => 'JOIN2026',
            'description' => 'Projet de groupe avec évaluation par les pairs.',
        ]);

        $course->students()->attach($students->pluck('id'));

        $project = Project::create([
            'course_id' => $course->id,
            'title' => 'Plateforme collaborative',
            'description' => 'Concevoir une solution d\'évaluation entre pairs.',
            'starts_at' => now()->subWeek(),
            'ends_at' => now()->addWeeks(3),
        ]);

        $rubric = Rubric::create([
            'project_id' => $project->id,
            'name' => 'Grille inter-groupe',
        ]);

        $rubric->criteria()->createMany([
            ['label' => 'Qualité du livrable', 'weight' => 40, 'max_score' => 5, 'sort_order' => 0],
            ['label' => 'Clarté de la présentation', 'weight' => 30, 'max_score' => 5, 'sort_order' => 1],
            ['label' => 'Innovation', 'weight' => 30, 'max_score' => 5, 'sort_order' => 2],
        ]);

        $groupA = Group::create([
            'project_id' => $project->id,
            'created_by' => $students[0]->id,
            'name' => 'Équipe Alpha',
            'invite_code' => 'ALPHA001',
        ]);
        $groupA->members()->attach([
            $students[0]->id => ['is_leader' => true],
            $students[1]->id => ['is_leader' => false],
        ]);
        $groupA->submission()->create(['status' => 'submitted', 'submitted_at' => now()]);

        $groupB = Group::create([
            'project_id' => $project->id,
            'created_by' => $students[2]->id,
            'name' => 'Équipe Beta',
            'invite_code' => 'BETA002',
        ]);
        $groupB->members()->attach([
            $students[2]->id => ['is_leader' => true],
            $students[3]->id => ['is_leader' => false],
            $students[4]->id => ['is_leader' => false],
        ]);
        $groupB->submission()->create(['status' => 'pending']);

        app(PeerEvaluationSyncService::class)->syncForProject($project);
    }
}
