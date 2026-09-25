<?php

namespace Tests\Feature;

use App\Enums\DeliverableType;
use App\Enums\EvaluationType;
use App\Enums\SubmissionStatus;
use App\Models\Course;
use App\Models\Group;
use App\Models\PeerEvaluation;
use App\Models\Project;
use App\Models\Submission;
use App\Models\User;
use App\Services\PeerEvaluationSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DeliverableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('public');
    }

    public function test_professor_sets_the_deliverable_type_when_creating_a_project(): void
    {
        $professor = User::factory()->professor()->create();
        $course = Course::create([
            'professor_id' => $professor->id,
            'title' => 'Cours',
            'code' => 'C-1',
            'join_code' => 'JOIN1',
        ]);

        $this->actingAs($professor)
            ->post(route('professor.courses.projects.store', $course), [
                'title' => 'Projet vidéo',
                'deliverable_type' => DeliverableType::Youtube->value,
                'starts_at' => '2026-09-01',
                'ends_at' => '2026-10-01',
            ])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('projects', [
            'course_id' => $course->id,
            'title' => 'Projet vidéo',
            'deliverable_type' => DeliverableType::Youtube->value,
        ]);
    }

    public function test_only_the_course_professor_can_change_the_deliverable_type(): void
    {
        $owner = User::factory()->professor()->create();
        $other = User::factory()->professor()->create();
        $student = User::factory()->create();

        $project = Project::create([
            'course_id' => Course::create([
                'professor_id' => $owner->id,
                'title' => 'Cours',
                'code' => 'C-1',
                'join_code' => 'JOIN1',
            ])->id,
            'title' => 'Projet',
            'deliverable_type' => DeliverableType::None,
        ]);

        $this->actingAs($owner)
            ->put(route('professor.projects.deliverable.update', $project), [
                'deliverable_type' => DeliverableType::Image->value,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(DeliverableType::Image, $project->fresh()->deliverable_type);

        $this->actingAs($other)
            ->put(route('professor.projects.deliverable.update', $project), [
                'deliverable_type' => DeliverableType::Video->value,
            ])
            ->assertForbidden();

        $this->actingAs($student)
            ->put(route('professor.projects.deliverable.update', $project), [
                'deliverable_type' => DeliverableType::File->value,
            ])
            ->assertForbidden();

        $this->actingAs($owner)
            ->put(route('professor.projects.deliverable.update', $project), [
                'deliverable_type' => 'audio',
            ])
            ->assertSessionHasErrors('deliverable_type');

        $this->assertSame(DeliverableType::Image, $project->fresh()->deliverable_type);
    }

    #[DataProvider('deliverableTypes')]
    public function test_group_member_can_submit_each_deliverable_type(DeliverableType $type): void
    {
        [$author, $reviewer, $group] = $this->seedProject($type);

        $submission = $group->submission;
        $this->assertSame(SubmissionStatus::Pending, $submission->status);
        $this->assertNull($submission->submitted_at);

        $this->postSubmission($author, $group, $type)
            ->assertRedirect()
            ->assertSessionHas('success');

        $submission->refresh();

        $this->assertSame(1, $group->submission()->count());
        $this->assertSame(SubmissionStatus::Submitted, $submission->status);
        $this->assertNotNull($submission->submitted_at);
        $this->assertStoredContent($type, $submission);

        $evaluation = PeerEvaluation::query()
            ->where('reviewer_id', $reviewer->id)
            ->where('type', EvaluationType::Inter)
            ->where('reviewee_group_id', $group->id)
            ->firstOrFail();

        $this->actingAs($reviewer)
            ->get(route('student.evaluations.show', $evaluation))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Student/Evaluations/Show')
                ->where('evaluation.deliverable.type', $type->value)
                ->where('evaluation.deliverable.type_label', $type->label())
                ->where('evaluation.deliverable.submitted', true)
                ->where('evaluation.deliverable.message', $this->expectedMessage($type))
                ->where('evaluation.deliverable.url', $this->expectedUrl($type, $submission))
                ->where('evaluation.deliverable.link', $this->expectedLink($type))
                ->where('evaluation.deliverable.embed_url', $this->expectedEmbed($type))
                ->where('evaluation.deliverable.original_name', $this->expectedOriginalName($type)));
    }

    public function test_submission_rejects_content_of_the_wrong_type(): void
    {
        [$author, , $imageGroup] = $this->seedProject(DeliverableType::Image);

        $this->actingAs($author)
            ->post(route('student.groups.submission.store', $imageGroup), [
                'file' => UploadedFile::fake()->create('notes.pdf', 40, 'application/pdf'),
            ])
            ->assertSessionHasErrors('file');

        $imageGroup->submission->refresh();
        $this->assertSame(SubmissionStatus::Pending, $imageGroup->submission->status);
        $this->assertNull($imageGroup->submission->submitted_at);
        $this->assertNull($imageGroup->submission->file_path);

        [, , $videoGroup] = $this->seedProject(DeliverableType::Video);
        $videoAuthor = $videoGroup->members()->first();

        $this->actingAs($videoAuthor)
            ->post(route('student.groups.submission.store', $videoGroup), [
                'file' => UploadedFile::fake()->image('photo.jpg'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertSame(SubmissionStatus::Pending, $videoGroup->submission->fresh()->status);

        [, , $youtubeGroup] = $this->seedProject(DeliverableType::Youtube);
        $youtubeAuthor = $youtubeGroup->members()->first();

        $this->actingAs($youtubeAuthor)
            ->post(route('student.groups.submission.store', $youtubeGroup), [
                'url' => 'https://vimeo.com/123456789',
            ])
            ->assertSessionHasErrors('url');

        $youtubeGroup->submission->refresh();
        $this->assertSame(SubmissionStatus::Pending, $youtubeGroup->submission->status);
        $this->assertNull($youtubeGroup->submission->url);
        $this->assertNull($youtubeGroup->submission->submitted_at);

        Storage::disk('public')->assertDirectoryEmpty('submissions');
    }

    public function test_only_group_members_can_submit(): void
    {
        [$author, $reviewer, $group] = $this->seedProject(DeliverableType::None);
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->post(route('student.groups.submission.store', $group))
            ->assertForbidden();

        $this->actingAs($reviewer)
            ->post(route('student.groups.submission.store', $group))
            ->assertForbidden();

        $this->assertSame(SubmissionStatus::Pending, $group->submission->fresh()->status);

        $this->actingAs($author)
            ->post(route('student.groups.submission.store', $group))
            ->assertRedirect();

        $this->assertSame(SubmissionStatus::Submitted, $group->submission->fresh()->status);
    }

    /**
     * @return array<string, array{0: DeliverableType}>
     */
    public static function deliverableTypes(): array
    {
        return [
            'rien' => [DeliverableType::None],
            'fichier' => [DeliverableType::File],
            'lien' => [DeliverableType::Link],
            'image' => [DeliverableType::Image],
            'vidéo' => [DeliverableType::Video],
            'vidéo youtube' => [DeliverableType::Youtube],
        ];
    }

    /**
     * @return array{0: User, 1: User, 2: Group}
     */
    private function seedProject(DeliverableType $type): array
    {
        $professor = User::factory()->professor()->create();
        $author = User::factory()->create();
        $reviewer = User::factory()->create();

        $project = Project::create([
            'course_id' => Course::create([
                'professor_id' => $professor->id,
                'title' => 'Cours',
                'code' => 'C-'.str()->random(6),
                'join_code' => strtoupper(str()->random(8)),
            ])->id,
            'title' => 'Projet',
            'deliverable_type' => $type,
        ]);

        $group = Group::create([
            'project_id' => $project->id,
            'created_by' => $author->id,
            'name' => 'Auteurs',
        ]);
        $group->members()->attach($author->id, ['is_leader' => true]);
        $group->submission()->create(['status' => SubmissionStatus::Pending]);

        $readers = Group::create([
            'project_id' => $project->id,
            'created_by' => $reviewer->id,
            'name' => 'Lecteurs',
        ]);
        $readers->members()->attach($reviewer->id, ['is_leader' => true]);
        $readers->submission()->create(['status' => SubmissionStatus::Pending]);

        app(PeerEvaluationSyncService::class)->syncForProject($project);

        return [$author, $reviewer, $group->fresh('submission')];
    }

    private function postSubmission(User $user, Group $group, DeliverableType $type): TestResponse
    {
        $payload = match ($type) {
            DeliverableType::None => [],
            DeliverableType::File => [
                'file' => UploadedFile::fake()->create('rapport.pdf', 120, 'application/pdf'),
            ],
            DeliverableType::Link => [
                'url' => 'https://example.com/livrable',
            ],
            DeliverableType::Image => [
                'file' => UploadedFile::fake()->image('schema.png'),
            ],
            DeliverableType::Video => [
                'file' => UploadedFile::fake()->create('demo.mp4', 200, 'video/mp4'),
            ],
            DeliverableType::Youtube => [
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ],
        };

        return $this->actingAs($user)->post(
            route('student.groups.submission.store', $group),
            $payload,
        );
    }

    private function assertStoredContent(DeliverableType $type, Submission $submission): void
    {
        if ($type->storesFile()) {
            $this->assertFileStored($submission, $this->expectedOriginalName($type));

            return;
        }

        $this->assertNull($submission->file_path);
        $this->assertSame($this->expectedLink($type), $submission->url);
    }

    private function assertFileStored(Submission $submission, string $originalName): void
    {
        $this->assertNotNull($submission->file_path);
        $this->assertNull($submission->url);
        $this->assertSame($originalName, $submission->original_name);
        Storage::disk('public')->assertExists($submission->file_path);
        $this->assertStringStartsWith('submissions/', $submission->file_path);
    }

    private function expectedMessage(DeliverableType $type): ?string
    {
        return $type === DeliverableType::None
            ? 'Aucun livrable n\'a été demandé.'
            : null;
    }

    private function expectedUrl(DeliverableType $type, Submission $submission): ?string
    {
        if (! $type->storesFile()) {
            return null;
        }

        return Storage::disk('public')->url($submission->file_path);
    }

    private function expectedLink(DeliverableType $type): ?string
    {
        return match ($type) {
            DeliverableType::Link => 'https://example.com/livrable',
            DeliverableType::Youtube => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            default => null,
        };
    }

    private function expectedEmbed(DeliverableType $type): ?string
    {
        return $type === DeliverableType::Youtube
            ? 'https://www.youtube.com/embed/dQw4w9WgXcQ'
            : null;
    }

    private function expectedOriginalName(DeliverableType $type): ?string
    {
        return match ($type) {
            DeliverableType::File => 'rapport.pdf',
            DeliverableType::Image => 'schema.png',
            DeliverableType::Video => 'demo.mp4',
            default => null,
        };
    }
}
