<?php

namespace Tests\Feature;

use App\Enums\DeliverableType;
use App\Enums\SubmissionStatus;
use App\Models\Course;
use App\Models\Group;
use App\Models\GroupMessage;
use App\Models\Project;
use App\Models\Rubric;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourseProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_professor_can_update_a_project_they_own(): void
    {
        $professor = User::factory()->professor()->create();
        [$course, $project] = $this->courseAndProject($professor);

        $this->actingAs($professor)
            ->put(route('professor.projects.update', $project), [
                'title' => 'Nouveau titre',
                'description' => 'Nouvelle description',
                'deliverable_type' => DeliverableType::Link->value,
                'starts_at' => '2026-09-02',
                'ends_at' => '2026-11-01',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Projet mis à jour.');

        $project->refresh();
        $this->assertSame('Nouveau titre', $project->title);
        $this->assertSame('Nouvelle description', $project->description);
        $this->assertSame(DeliverableType::Link, $project->deliverable_type);
        $this->assertSame('2026-09-02', $project->starts_at->toDateString());
        $this->assertSame('2026-11-01', $project->ends_at->toDateString());
        $this->assertSame($course->id, $project->course_id);
    }

    public function test_empty_project_can_be_deleted_without_touching_the_course(): void
    {
        $professor = User::factory()->professor()->create();
        [$course, $project] = $this->courseAndProject($professor);
        $student = User::factory()->create();
        $course->students()->attach($student->id);

        $this->actingAs($professor)
            ->delete(route('professor.projects.destroy', $project))
            ->assertRedirect()
            ->assertSessionHas('success', 'Projet effacé.');

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        $this->assertDatabaseHas('courses', ['id' => $course->id]);
        $this->assertDatabaseHas('course_user', [
            'course_id' => $course->id,
            'user_id' => $student->id,
        ]);
        $this->assertDatabaseHas('users', ['id' => $student->id]);
    }

    public function test_a_project_with_groups_is_kept_until_the_purge_is_confirmed(): void
    {
        $professor = User::factory()->professor()->create();
        [$course, $project] = $this->courseAndProject($professor);
        $this->groupOn($project, User::factory()->create());

        $this->actingAs($professor)
            ->delete(route('professor.projects.destroy', $project))
            ->assertSessionHasErrors('purge');

        $this->assertDatabaseHas('projects', ['id' => $project->id]);
        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }

    public function test_confirming_purge_removes_the_project_and_deliverables_only(): void
    {
        Storage::fake('public');

        $professor = User::factory()->professor()->create();
        [$course, $project] = $this->courseAndProject($professor, 'Projet A');
        $other = Project::create([
            'course_id' => $course->id,
            'title' => 'Projet B',
            'deliverable_type' => DeliverableType::None,
            'starts_at' => '2026-09-01',
            'ends_at' => '2026-10-01',
        ]);

        $student = User::factory()->create();
        $course->students()->attach($student->id);
        $group = $this->groupOn($project, $student);
        $otherGroup = $this->groupOn($other, $student);

        Storage::disk('public')->put('submissions/'.$group->id.'/rendu.pdf', 'pdf');
        $submission = $group->submission()->create([
            'status' => SubmissionStatus::Submitted,
            'file_path' => 'submissions/'.$group->id.'/rendu.pdf',
            'original_name' => 'rendu.pdf',
            'submitted_at' => now(),
        ]);
        $message = GroupMessage::create([
            'group_id' => $group->id,
            'user_id' => $student->id,
            'body' => 'Bonjour',
        ]);
        $rubric = Rubric::create([
            'project_id' => $project->id,
            'name' => 'Grille',
        ]);

        $this->actingAs($professor)
            ->delete(route('professor.projects.destroy', $project), [
                'purge' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Projet et livrables effacés.');

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        $this->assertDatabaseMissing('groups', ['id' => $group->id]);
        $this->assertDatabaseMissing('submissions', ['id' => $submission->id]);
        $this->assertDatabaseMissing('group_messages', ['id' => $message->id]);
        $this->assertDatabaseMissing('rubrics', ['id' => $rubric->id]);
        Storage::disk('public')->assertMissing('submissions/'.$group->id.'/rendu.pdf');

        $this->assertDatabaseHas('projects', ['id' => $other->id]);
        $this->assertDatabaseHas('groups', ['id' => $otherGroup->id]);
        $this->assertDatabaseHas('group_user', [
            'group_id' => $otherGroup->id,
            'user_id' => $student->id,
        ]);
        $this->assertDatabaseHas('course_user', [
            'course_id' => $course->id,
            'user_id' => $student->id,
        ]);
        $this->assertDatabaseHas('users', ['id' => $student->id]);
    }

    public function test_professor_can_update_a_course_they_own(): void
    {
        $professor = User::factory()->professor()->create();
        [$course] = $this->courseAndProject($professor);

        $this->actingAs($professor)
            ->put(route('professor.courses.update', $course), [
                'title' => 'Cours renommé',
                'description' => 'Description mise à jour',
                'code' => 'new-code',
                'join_code' => 'newjoin',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Cours mis à jour.');

        $course->refresh();
        $this->assertSame('Cours renommé', $course->title);
        $this->assertSame('Description mise à jour', $course->description);
        $this->assertSame('NEW-CODE', $course->code);
        $this->assertSame('NEWJOIN', $course->join_code);
    }

    public function test_a_course_with_projects_is_not_deleted(): void
    {
        $professor = User::factory()->professor()->create();
        [$course, $project] = $this->courseAndProject($professor);
        $student = User::factory()->create();
        $course->students()->attach($student->id);

        $this->actingAs($professor)
            ->delete(route('professor.courses.destroy', $course), [
                'delete_students' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Supprimez d\'abord les projets de ce cours.');

        $this->assertDatabaseHas('courses', ['id' => $course->id]);
        $this->assertDatabaseHas('projects', ['id' => $project->id]);
        $this->assertDatabaseHas('users', ['id' => $student->id]);
    }

    public function test_an_empty_course_can_be_deleted_while_keeping_students(): void
    {
        $professor = User::factory()->professor()->create();
        $course = $this->emptyCourse($professor);
        $student = User::factory()->create();
        $course->students()->attach($student->id);

        $this->actingAs($professor)
            ->delete(route('professor.courses.destroy', $course), [
                'delete_students' => false,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Cours effacé.');

        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
        $this->assertDatabaseMissing('course_user', ['course_id' => $course->id]);
        $this->assertDatabaseHas('users', ['id' => $student->id]);
    }

    public function test_an_empty_course_can_delete_students_who_belong_only_to_it(): void
    {
        $professor = User::factory()->professor()->create();
        $course = $this->emptyCourse($professor, 'C-EMPTY', 'EMPTY1');
        $other = $this->emptyCourse($professor, 'C-OTHER', 'OTHER1');
        $onlyHere = User::factory()->create();
        $alsoElsewhere = User::factory()->create();
        $course->students()->attach([$onlyHere->id, $alsoElsewhere->id]);
        $other->students()->attach($alsoElsewhere->id);

        $this->actingAs($professor)
            ->delete(route('professor.courses.destroy', $course), [
                'delete_students' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
        $this->assertDatabaseMissing('users', ['id' => $onlyHere->id]);
        $this->assertDatabaseHas('users', ['id' => $alsoElsewhere->id]);
        $this->assertDatabaseHas('courses', ['id' => $other->id]);
        $this->assertDatabaseHas('course_user', [
            'course_id' => $other->id,
            'user_id' => $alsoElsewhere->id,
        ]);
    }

    public function test_another_professor_cannot_edit_or_delete(): void
    {
        $owner = User::factory()->professor()->create();
        $other = User::factory()->professor()->create();
        [$course, $project] = $this->courseAndProject($owner);

        $this->actingAs($other)
            ->put(route('professor.projects.update', $project), [
                'title' => 'Intrusion',
                'deliverable_type' => DeliverableType::File->value,
                'starts_at' => '2026-09-01',
                'ends_at' => '2026-10-01',
            ])
            ->assertForbidden();

        $this->actingAs($other)
            ->delete(route('professor.projects.destroy', $project), ['purge' => true])
            ->assertForbidden();

        $this->actingAs($other)
            ->put(route('professor.courses.update', $course), [
                'title' => 'Intrusion',
                'code' => 'HACK',
                'join_code' => 'HACK1',
            ])
            ->assertForbidden();

        $this->actingAs($other)
            ->delete(route('professor.courses.destroy', $course), [
                'delete_students' => false,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'title' => 'Projet']);
        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }

    /**
     * @return array{0: Course, 1: Project}
     */
    private function courseAndProject(User $professor, string $title = 'Projet'): array
    {
        $course = $this->emptyCourse($professor);
        $project = Project::create([
            'course_id' => $course->id,
            'title' => $title,
            'deliverable_type' => DeliverableType::None,
            'starts_at' => '2026-09-01',
            'ends_at' => '2026-10-01',
        ]);

        return [$course, $project];
    }

    private function emptyCourse(User $professor, string $code = 'C-1', string $join = 'JOIN1'): Course
    {
        return Course::create([
            'professor_id' => $professor->id,
            'title' => 'Cours',
            'code' => $code,
            'join_code' => $join,
        ]);
    }

    private function groupOn(Project $project, User $student): Group
    {
        $group = Group::create([
            'project_id' => $project->id,
            'created_by' => $student->id,
            'name' => 'Groupe '.$project->id,
        ]);
        $group->members()->attach($student->id, ['is_leader' => true]);

        return $group;
    }
}
