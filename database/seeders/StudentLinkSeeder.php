<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Group;
use App\Models\GroupMessage;
use App\Models\Project;
use App\Models\Rubric;
use App\Models\User;
use App\Services\PeerEvaluationSyncService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StudentLinkSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        $this->seedUser('admin@studentlink.test', 'Admin StudentLink', UserRole::Admin, $password);
        $professor = $this->seedUser('prof@studentlink.test', 'Prof. Martin', UserRole::Professor, $password);

        $students = collect([
            $this->seedUser('alice@studentlink.test', 'Alice Dupont', UserRole::Student, $password),
            $this->seedUser('bob@studentlink.test', 'Bob Martin', UserRole::Student, $password),
            $this->seedUser('claire@studentlink.test', 'Claire Leroy', UserRole::Student, $password),
            $this->seedUser('david@studentlink.test', 'David Chen', UserRole::Student, $password),
            $this->seedUser('emma@studentlink.test', 'Emma Rossi', UserRole::Student, $password),
        ]);

        $course = Course::firstOrCreate(
            ['join_code' => 'JOIN2026'],
            [
                'professor_id' => $professor->id,
                'title' => 'Management de projet digital',
                'code' => 'MPWD-2026',
                'description' => 'Projet de groupe avec évaluation par les pairs.',
                'allowed_email_domains' => null,
            ],
        );

        $course->update([
            'professor_id' => $professor->id,
        ]);

        $course->students()->syncWithoutDetaching($students->pluck('id'));

        $project = Project::firstOrCreate(
            ['course_id' => $course->id, 'title' => 'Plateforme collaborative'],
            [
                'description' => 'Concevoir une solution d\'évaluation entre pairs.',
                'starts_at' => now()->subWeek(),
                'ends_at' => now()->addWeeks(3),
            ],
        );

        $rubric = Rubric::firstOrCreate(
            ['project_id' => $project->id],
            ['name' => 'Grille inter-groupe'],
        );

        if ($rubric->criteria()->count() === 0) {
            $rubric->criteria()->createMany([
                ['label' => 'Qualité du livrable', 'weight' => 40, 'max_score' => 5, 'sort_order' => 0],
                ['label' => 'Clarté de la présentation', 'weight' => 30, 'max_score' => 5, 'sort_order' => 1],
                ['label' => 'Innovation', 'weight' => 30, 'max_score' => 5, 'sort_order' => 2],
            ]);
        }

        $groupA = Group::firstOrCreate(
            ['invite_code' => 'ALPHA001'],
            [
                'project_id' => $project->id,
                'created_by' => $students[0]->id,
                'name' => 'Équipe Alpha',
            ],
        );
        $groupA->members()->syncWithoutDetaching([
            $students[0]->id => ['is_leader' => true],
            $students[1]->id => ['is_leader' => false],
        ]);
        $groupA->submission()->firstOrCreate(
            ['group_id' => $groupA->id],
            ['status' => 'submitted', 'submitted_at' => now()],
        );

        $groupB = Group::firstOrCreate(
            ['invite_code' => 'BETA002'],
            [
                'project_id' => $project->id,
                'created_by' => $students[2]->id,
                'name' => 'Équipe Beta',
            ],
        );
        $groupB->members()->syncWithoutDetaching([
            $students[2]->id => ['is_leader' => true],
            $students[3]->id => ['is_leader' => false],
            $students[4]->id => ['is_leader' => false],
        ]);
        $groupB->submission()->firstOrCreate(
            ['group_id' => $groupB->id],
            ['status' => 'pending'],
        );

        app(PeerEvaluationSyncService::class)->syncForProject($project);

        if ($groupA->messages()->count() === 0) {
            GroupMessage::create([
                'group_id' => $groupA->id,
                'user_id' => $students[0]->id,
                'body' => 'Salut l\'équipe — on valide le plan pour vendredi ?',
            ]);
            GroupMessage::create([
                'group_id' => $groupA->id,
                'user_id' => $students[1]->id,
                'body' => 'Oui, je m\'occupe du wireframe ce soir.',
            ]);
        }
    }

    private function seedUser(string $email, string $name, UserRole $role, string $password): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'role' => $role,
                'password' => $password,
                'email_verified_at' => now(),
            ],
        );
    }
}
