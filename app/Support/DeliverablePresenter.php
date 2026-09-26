<?php

namespace App\Support;

use App\Enums\DeliverableType;
use App\Enums\SubmissionStatus;
use App\Models\Project;
use App\Models\Submission;
use Illuminate\Support\Facades\Storage;

class DeliverablePresenter
{
    /**
     * @return array{
     *     type: string,
     *     type_label: string,
     *     submitted: bool,
     *     url: ?string,
     *     link: ?string,
     *     embed_url: ?string,
     *     original_name: ?string,
     *     message: ?string
     * }
     */
    public function present(Project $project, ?Submission $submission): array
    {
        $type = $project->deliverable_type ?? DeliverableType::None;
        $submitted = $submission?->status === SubmissionStatus::Submitted;

        $data = [
            'type' => $type->value,
            'type_label' => $type->label(),
            'submitted' => $submitted,
            'url' => null,
            'link' => null,
            'embed_url' => null,
            'original_name' => null,
            'message' => null,
        ];

        if ($type === DeliverableType::None) {
            $data['message'] = __('Aucun livrable n\'a été demandé.');

            return $data;
        }

        if (! $submitted || $submission === null) {
            $data['message'] = __('Ce groupe n\'a pas encore rendu son livrable.');

            return $data;
        }

        if ($type === DeliverableType::Link) {
            if (! $this->isHttpUrl($submission->url)) {
                $data['message'] = __('Le lien du livrable n\'est pas disponible.');

                return $data;
            }

            $data['link'] = $submission->url;

            return $data;
        }

        if ($type === DeliverableType::Youtube) {
            $embed = YoutubeUrl::embedUrl($submission->url);

            if ($embed === null) {
                $data['message'] = __('La vidéo YouTube n\'est pas disponible.');

                return $data;
            }

            $data['link'] = $submission->url;
            $data['embed_url'] = $embed;

            return $data;
        }

        if ($type->storesFile()) {
            if (! $submission->file_path || ! Storage::disk('public')->exists($submission->file_path)) {
                $data['message'] = __('Le fichier du livrable n\'est pas disponible.');

                return $data;
            }

            $data['url'] = Storage::disk('public')->url($submission->file_path);
            $data['original_name'] = $submission->original_name;

            return $data;
        }

        return $data;
    }

    private function isHttpUrl(?string $url): bool
    {
        return is_string($url) && preg_match('#^https?://#i', $url) === 1;
    }
}
