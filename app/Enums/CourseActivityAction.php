<?php

namespace App\Enums;

enum CourseActivityAction: string
{
    case JoinedCourse = 'joined_course';
    case JoinedGroup = 'joined_group';
    case LeftGroup = 'left_group';
    case SubmittedDeliverable = 'submitted_deliverable';
    case SentEvaluation = 'sent_evaluation';
    case PostedMessage = 'posted_message';

    public function label(?string $subject = null): string
    {
        $name = is_string($subject) ? trim($subject) : '';

        return match ($this) {
            self::JoinedCourse => __('A rejoint le cours'),
            self::JoinedGroup => $name !== ''
                ? __('A rejoint le groupe « :name »', ['name' => $name])
                : __('A rejoint un groupe'),
            self::LeftGroup => $name !== ''
                ? __('A quitté le groupe « :name »', ['name' => $name])
                : __('A quitté un groupe'),
            self::SubmittedDeliverable => $name !== ''
                ? __('A déposé un livrable pour « :name »', ['name' => $name])
                : __('A déposé un livrable'),
            self::SentEvaluation => $name !== ''
                ? __('A envoyé une évaluation pour « :name »', ['name' => $name])
                : __('A envoyé une évaluation'),
            self::PostedMessage => $name !== ''
                ? __('A publié un message dans « :name »', ['name' => $name])
                : __('A publié un message'),
        };
    }
}
