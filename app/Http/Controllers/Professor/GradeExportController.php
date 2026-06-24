<?php

namespace App\Http\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\GradeExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GradeExportController extends Controller
{
    public function __construct(
        private GradeExportService $exportService,
    ) {}

    public function __invoke(Project $project): StreamedResponse
    {
        if ($project->course->professor_id !== auth()->id()) {
            abort(403);
        }

        return $this->exportService->exportProjectCsv($project);
    }
}
