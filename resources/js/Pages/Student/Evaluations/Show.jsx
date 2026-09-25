import DeliverablePreview from '@/Components/DeliverablePreview';
import FlashMessage from '@/Components/FlashMessage';
import Icon from '@/Components/Icon';
import PrimaryButton from '@/Components/PrimaryButton';
import ScoreSlider from '@/Components/ScoreSlider';
import StudentLayout from '@/Layouts/StudentLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Show({ evaluation }) {
    const initialScores = Object.fromEntries(
        evaluation.criteria.map((c) => [c.id, c.score]),
    );

    const { data, setData, put, processing, errors } = useForm({
        scores: initialScores,
    });

    const isInter = evaluation.type === 'inter';
    const readOnly = evaluation.status === 'completed';

    const setScore = (criterionId, value) => {
        setData('scores', { ...data.scores, [criterionId]: value });
    };

    const submit = (e) => {
        e.preventDefault();
        put(route('student.evaluations.update', evaluation.id));
    };

    return (
        <StudentLayout title="Évaluation">
            <Head title={`Évaluer ${evaluation.target_label}`} />
            <FlashMessage />

            <Link
                href={route('student.evaluations.index')}
                className="mb-4 inline-flex items-center gap-1 text-sm text-primary-container"
            >
                <Icon name="arrow_back" className="text-base" />
                Retour
            </Link>

            <div className="mb-6 rounded-studentlink border border-primary-container/20 bg-card p-4">
                <span
                    className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ${
                        isInter
                            ? 'bg-secondary/10 text-secondary'
                            : 'bg-primary-container/10 text-primary-container'
                    }`}
                >
                    <Icon
                        name={isInter ? 'groups' : 'person'}
                        className="text-sm"
                        filled
                    />
                    {evaluation.type_label}
                </span>
                <h2 className="mt-2 text-lg font-semibold text-on-surface">
                    {evaluation.target_label}
                </h2>
                <p className="text-sm text-on-surface/60">{evaluation.project.title}</p>
                {evaluation.project.ends_at && (
                    <p className="mt-2 flex items-center gap-1 text-xs text-on-surface/50">
                        <Icon name="schedule" className="text-sm" />
                        Échéance : {evaluation.project.ends_at}
                    </p>
                )}
            </div>

            {isInter && evaluation.deliverable && (
                <section className="mb-6 rounded-studentlink border border-primary-container/20 bg-card p-4">
                    <h3 className="mb-3 text-sm font-semibold text-on-surface">
                        Livrable · {evaluation.deliverable.type_label}
                    </h3>
                    <DeliverablePreview deliverable={evaluation.deliverable} />
                </section>
            )}

            {isInter && (
                <p className="mb-4 border-l-4 border-secondary pl-3 text-sm italic text-on-surface/70">
                    Notez la qualité du travail produit par ce groupe. Soyez constructif
                    et objectif.
                </p>
            )}

            {!isInter && (
                <p className="mb-4 border-l-4 border-primary-container pl-3 text-sm italic text-on-surface/70">
                    Évaluez l&apos;implication de ce coéquipier au sein de votre groupe.
                </p>
            )}

            <form onSubmit={submit} className="space-y-6">
                {evaluation.criteria.map((criterion) => (
                    <div
                        key={criterion.id}
                        className="rounded-studentlink border border-outline-variant/30 bg-card p-4"
                    >
                        <ScoreSlider
                            id={`criterion-${criterion.id}`}
                            label={criterion.label}
                            hint={`Poids ${criterion.weight} %`}
                            max={criterion.max_score}
                            value={data.scores[criterion.id]}
                            onChange={(value) => setScore(criterion.id, value)}
                            disabled={readOnly}
                        />
                        {errors[`scores.${criterion.id}`] && (
                            <p className="mt-2 text-sm text-red-600">
                                {errors[`scores.${criterion.id}`]}
                            </p>
                        )}
                    </div>
                ))}

                {!readOnly && (
                    <PrimaryButton disabled={processing} className="w-full justify-center">
                        Enregistrer l&apos;évaluation
                    </PrimaryButton>
                )}

                {readOnly && (
                    <p className="text-center text-sm text-tertiary">
                        Évaluation déjà enregistrée.
                    </p>
                )}
            </form>
        </StudentLayout>
    );
}
