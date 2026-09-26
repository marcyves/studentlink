<?php

namespace App\Http\Controllers\Student;

use App\Enums\DeliverableType;
use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Submission;
use App\Rules\YoutubeUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SubmissionController extends Controller
{
    public function store(Request $request, Group $group): RedirectResponse
    {
        $group->load('project', 'submission');

        if (! $group->members()->where('users.id', $request->user()->id)->exists()) {
            abort(403);
        }

        $type = $group->project->deliverable_type ?? DeliverableType::None;
        $validated = $this->validateForType($request, $type);

        $submission = $group->submission ?? $group->submission()->create([
            'status' => SubmissionStatus::Pending,
        ]);

        $this->fillContent($submission, $group, $type, $request, $validated);

        $submission->status = SubmissionStatus::Submitted;
        $submission->submitted_at = now();
        $submission->save();

        return back()->with('success', __('Livrable enregistré.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validateForType(Request $request, DeliverableType $type): array
    {
        $messages = [
            'file.required' => __('Déposez un fichier.'),
            'file.file' => __('Déposez un fichier valide.'),
            'file.image' => __('Le fichier doit être une image.'),
            'file.mimetypes' => __('Le fichier doit être une vidéo.'),
            'file.max' => __('Le fichier dépasse la taille autorisée.'),
            'url.required' => __('Indiquez une adresse.'),
            'url.url' => __('Indiquez une adresse http ou https valide.'),
            'url.max' => __('Cette adresse est trop longue.'),
        ];

        $rules = match ($type) {
            DeliverableType::None => [],
            DeliverableType::Link => [
                'url' => ['required', 'string', 'max:2048', 'url:http,https'],
            ],
            DeliverableType::Youtube => [
                'url' => ['required', 'string', 'max:2048', 'url:http,https', new YoutubeUrl],
            ],
            DeliverableType::File => [
                'file' => ['required', 'file', 'max:'.$type->maxKilobytes()],
            ],
            DeliverableType::Image => [
                'file' => ['required', 'file', 'image', 'max:'.$type->maxKilobytes()],
            ],
            DeliverableType::Video => [
                'file' => ['required', 'file', 'mimetypes:video/*', 'max:'.$type->maxKilobytes()],
            ],
        };

        return $request->validate($rules, $messages);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function fillContent(
        Submission $submission,
        Group $group,
        DeliverableType $type,
        Request $request,
        array $validated,
    ): void {
        if ($type->storesFile()) {
            /** @var UploadedFile $file */
            $file = $request->file('file');
            $previous = $submission->file_path;
            $path = $file->store('submissions/'.$group->id, 'public');

            if (is_string($previous) && $previous !== '' && $previous !== $path) {
                Storage::disk('public')->delete($previous);
            }

            $submission->fill([
                'url' => null,
                'file_path' => $path,
                'original_name' => $this->originalName($file),
            ]);

            return;
        }

        if (is_string($submission->file_path) && $submission->file_path !== '') {
            Storage::disk('public')->delete($submission->file_path);
        }

        $submission->fill([
            'url' => $type->storesUrl() ? $validated['url'] : null,
            'file_path' => null,
            'original_name' => null,
        ]);
    }

    private function originalName(UploadedFile $file): string
    {
        $name = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $name = trim($name);

        if ($name === '' || $name === '.' || $name === '..') {
            return 'fichier';
        }

        return mb_substr($name, 0, 255);
    }
}
