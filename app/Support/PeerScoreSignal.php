<?php

namespace App\Support;

use App\Enums\EvaluationType;
use App\Enums\PeerEvaluationStatus;
use App\Models\PeerEvaluation;
use Illuminate\Support\Collection;

class PeerScoreSignal
{
    /**
     * Mean gap, on the /100 scale, before a reviewer is shown to the professor.
     * Smaller gaps are ordinary spread from the point budget.
     */
    private const GAP = 25.0;

    /**
     * @param  Collection<int, int>|array<int, int>  $projectIds
     * @return array<int, list<array{reviewer_id: int, reviewer_name: string, type: string, kind: string, gap: int|null, targets: int}>>
     */
    public static function forProjects(Collection|array $projectIds): array
    {
        $ids = collect($projectIds)->map(fn ($id) => (int) $id)->unique()->values();
        $grouped = $ids->isEmpty()
            ? collect()
            : PeerEvaluation::query()
                ->whereIn('project_id', $ids)
                ->where('status', PeerEvaluationStatus::Completed)
                ->with(['reviewer:id,name', 'scores.criterion'])
                ->get()
                ->groupBy('project_id');

        $signals = [];

        foreach ($ids as $id) {
            $signals[$id] = self::signalsFor($grouped->get($id, collect()));
        }

        return $signals;
    }

    /**
     * @param  Collection<int, PeerEvaluation>  $evaluations
     * @return list<array{reviewer_id: int, reviewer_name: string, type: string, kind: string, gap: int|null, targets: int}>
     */
    private static function signalsFor(Collection $evaluations): array
    {
        $signals = [];

        foreach ([EvaluationType::Intra, EvaluationType::Inter] as $type) {
            array_push($signals, ...self::signalsForType(
                $evaluations->filter(fn (PeerEvaluation $evaluation) => $evaluation->type === $type)->values(),
                $type,
            ));
        }

        usort($signals, function (array $left, array $right): int {
            return ($right['gap'] ?? -1) <=> ($left['gap'] ?? -1);
        });

        return $signals;
    }

    /**
     * @param  Collection<int, PeerEvaluation>  $evaluations
     * @return list<array{reviewer_id: int, reviewer_name: string, type: string, kind: string, gap: int|null, targets: int}>
     */
    private static function signalsForType(Collection $evaluations, EvaluationType $type): array
    {
        $byTarget = [];

        foreach ($evaluations as $evaluation) {
            $targetId = $type === EvaluationType::Intra
                ? $evaluation->reviewee_user_id
                : $evaluation->reviewee_group_id;
            $note = PeerWeightedNote::of($evaluation);

            if ($targetId === null || $note === null) {
                continue;
            }

            $byTarget[$targetId][] = [
                'reviewer_id' => (int) $evaluation->reviewer_id,
                'reviewer_name' => $evaluation->reviewer?->name ?? '—',
                'note' => $note,
                'id' => (int) $evaluation->id,
            ];
        }

        $reviewers = [];

        foreach ($byTarget as $rows) {
            $rows = self::oneNotePerReviewer($rows);

            if (count($rows) < 2) {
                continue;
            }

            foreach ($rows as $row) {
                $others = [];

                foreach ($rows as $other) {
                    if ($other['reviewer_id'] !== $row['reviewer_id']) {
                        $others[] = $other['note'];
                    }
                }

                if ($others === []) {
                    continue;
                }

                $reviewers[$row['reviewer_id']]['name'] = $row['reviewer_name'];
                $reviewers[$row['reviewer_id']]['pairs'][] = [
                    'own' => $row['note'],
                    'consensus' => self::median($others),
                ];
            }
        }

        $signals = [];

        foreach ($reviewers as $reviewerId => $data) {
            $pairs = $data['pairs'];

            if (count($pairs) < 2) {
                continue;
            }

            $mean = array_sum(array_map(
                fn (array $pair) => $pair['own'] - $pair['consensus'],
                $pairs,
            )) / count($pairs);

            $kind = null;

            if ($mean >= self::GAP) {
                $kind = 'high';
            } elseif ($mean <= -self::GAP) {
                $kind = 'low';
            } elseif (self::orderDisagrees($pairs)) {
                $kind = 'order';
            }

            if ($kind === null) {
                continue;
            }

            $signals[] = [
                'reviewer_id' => (int) $reviewerId,
                'reviewer_name' => $data['name'],
                'type' => $type->value,
                'kind' => $kind,
                'gap' => $kind === 'order' ? null : (int) round(abs($mean)),
                'targets' => count($pairs),
            ];
        }

        return $signals;
    }

    /**
     * @param  list<array{reviewer_id: int, reviewer_name: string, note: float, id: int}>  $rows
     * @return list<array{reviewer_id: int, reviewer_name: string, note: float, id: int}>
     */
    private static function oneNotePerReviewer(array $rows): array
    {
        $kept = [];

        foreach ($rows as $row) {
            $current = $kept[$row['reviewer_id']] ?? null;

            if ($current === null || $row['id'] > $current['id']) {
                $kept[$row['reviewer_id']] = $row;
            }
        }

        return array_values($kept);
    }

    /**
     * @param  list<array{own: float, consensus: float}>  $pairs
     */
    private static function orderDisagrees(array $pairs): bool
    {
        if (count($pairs) < 3) {
            return false;
        }

        $own = array_column($pairs, 'own');
        $consensus = array_column($pairs, 'consensus');
        $correlation = self::spearman($own, $consensus);

        if ($correlation !== null) {
            return $correlation <= 0.0;
        }

        return self::range($own) < 10 && self::range($consensus) >= 30;
    }

    /**
     * @param  list<float>  $values
     */
    private static function median(array $values): float
    {
        sort($values, SORT_NUMERIC);
        $count = count($values);
        $middle = intdiv($count, 2);

        if ($count % 2 === 1) {
            return (float) $values[$middle];
        }

        return ($values[$middle - 1] + $values[$middle]) / 2;
    }

    /**
     * @param  list<float>  $values
     */
    private static function range(array $values): float
    {
        return max($values) - min($values);
    }

    /**
     * @param  list<float>  $left
     * @param  list<float>  $right
     */
    private static function spearman(array $left, array $right): ?float
    {
        $count = count($left);

        if ($count < 3 || $count !== count($right)) {
            return null;
        }

        $leftRanks = self::ranks($left);
        $rightRanks = self::ranks($right);
        $leftMean = array_sum($leftRanks) / $count;
        $rightMean = array_sum($rightRanks) / $count;
        $numerator = 0.0;
        $leftSquare = 0.0;
        $rightSquare = 0.0;

        for ($index = 0; $index < $count; $index++) {
            $leftDelta = $leftRanks[$index] - $leftMean;
            $rightDelta = $rightRanks[$index] - $rightMean;
            $numerator += $leftDelta * $rightDelta;
            $leftSquare += $leftDelta * $leftDelta;
            $rightSquare += $rightDelta * $rightDelta;
        }

        if ($leftSquare < 1e-9 || $rightSquare < 1e-9) {
            return null;
        }

        return $numerator / sqrt($leftSquare * $rightSquare);
    }

    /**
     * @param  list<float>  $values
     * @return list<float>
     */
    private static function ranks(array $values): array
    {
        $indexed = [];

        foreach ($values as $index => $value) {
            $indexed[] = ['index' => $index, 'value' => $value];
        }

        usort($indexed, fn (array $left, array $right) => $left['value'] <=> $right['value']);

        $ranks = array_fill(0, count($values), 0.0);
        $start = 0;

        while ($start < count($indexed)) {
            $end = $start;

            while (
                $end + 1 < count($indexed)
                && $indexed[$end + 1]['value'] == $indexed[$start]['value']
            ) {
                $end++;
            }

            $average = (($start + 1) + ($end + 1)) / 2;

            for ($cursor = $start; $cursor <= $end; $cursor++) {
                $ranks[$indexed[$cursor]['index']] = $average;
            }

            $start = $end + 1;
        }

        return $ranks;
    }
}
