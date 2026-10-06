import DeliverablePreview from '@/Components/DeliverablePreview';
import FlashMessage from '@/Components/FlashMessage';
import Icon from '@/Components/Icon';
import PrimaryButton from '@/Components/PrimaryButton';
import ScoreSlider from '@/Components/ScoreSlider';
import StudentLayout from '@/Layouts/StudentLayout';
import { useT } from '@/i18n';
import { Head, Link, useForm } from '@inertiajs/react';

function errorText(errors) {
    return Object.values(errors)
        .flatMap((error) => (Array.isArray(error) ? error : [error]))
        .filter((error) => typeof error === 'string' && error !== '')
        .join(' ');
}

export default function Show({ evaluation }) {
    const t = useT();
    const targets = evaluation.targets ?? [];
    const criteria = evaluation.criteria ?? [];
    const editableTargets = targets.filter((target) => !target.read_only);
    const readOnly = editableTargets.length === 0;

    const initialScores = Object.fromEntries(
        editableTargets.map((target) => [
            target.id,
            Object.fromEntries(
                criteria.map((criterion) => [
                    criterion.id,
                    target.scores?.[criterion.id] ?? 0,
                ]),
            ),
        ]),
    );

    const { data, setData, put, processing, errors } = useForm({
        scores: initialScores,
    });

    const isInter = evaluation.type === 'inter';
    const pageTitle = isInter
        ? t('Évaluer les autres groupes')
        : t('Évaluer vos coéquipiers');

    const scoreOf = (target, criterionId) => {
        if (target.read_only) {
            return Number(target.scores?.[criterionId] ?? 0);
        }

        return Number(data.scores[target.id]?.[criterionId] ?? 0);
    };

    const used = (criterionId) =>
        targets.reduce((sum, target) => sum + scoreOf(target, criterionId), 0);

    const overBudget = criteria.some(
        (criterion) => used(criterion.id) > criterion.pool,
    );

    const setScore = (targetId, criterionId, value) => {
        setData('scores', {
            ...data.scores,
            [targetId]: {
                ...data.scores[targetId],
                [criterionId]: value,
            },
        });
    };

    const submit = (e) => {
        e.preventDefault();

        if (readOnly || overBudget) {
            return;
        }

        put(route('student.evaluations.update', evaluation.id));
    };

    const budgetError = errorText(errors);

    return (
        <StudentLayout title={t('Évaluation')}>
            <Head title={pageTitle} />
            <FlashMessage />

            <Link
                href={route('student.evaluations.index')}
                className="mb-4 inline-flex items-center gap-1 text-sm text-primary-container"
            >
                <Icon name="arrow_back" className="text-base" />
                {t('Retour')}
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
                    {pageTitle}
                </h2>
                <p className="text-sm text-on-surface/60">{evaluation.project.title}</p>
                {evaluation.project.ends_at && (
                    <p className="mt-2 flex items-center gap-1 text-xs text-on-surface/50">
                        <Icon name="schedule" className="text-sm" />
                        {t('Échéance : :date', { date: evaluation.project.ends_at })}
                    </p>
                )}
            </div>

            {isInter && (
                <p className="mb-4 border-l-4 border-secondary pl-3 text-sm italic text-on-surface/70">
                    {t('Notez la qualité du travail produit par les autres groupes. Soyez constructif et objectif.')}
                </p>
            )}

            {!isInter && (
                <p className="mb-4 border-l-4 border-primary-container pl-3 text-sm italic text-on-surface/70">
                    {t("Évaluez l'implication de chaque coéquipier au sein de votre groupe.")}
                </p>
            )}

            {isInter && targets.some((target) => target.deliverable) && (
                <section className="mb-6 space-y-4">
                    {targets.map((target) => (
                        <div
                            key={target.id}
                            className="rounded-studentlink border border-primary-container/20 bg-card p-4"
                        >
                            <h3 className="mb-3 text-sm font-semibold text-on-surface">
                                {t('Livrable · :label', {
                                    label: target.deliverable?.type_label
                                        ? `${target.label} · ${target.deliverable.type_label}`
                                        : target.label,
                                })}
                            </h3>
                            {target.deliverable && (
                                <DeliverablePreview deliverable={target.deliverable} />
                            )}
                        </div>
                    ))}
                </section>
            )}

            <form onSubmit={submit} className="space-y-6">
                {criteria.map((criterion) => {
                    const left = criterion.pool - used(criterion.id);

                    return (
                        <div
                            key={criterion.id}
                            className="space-y-5 rounded-studentlink border border-outline-variant/30 bg-card p-4"
                        >
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <h3 className="text-sm font-semibold text-on-surface">
                                        {criterion.label}
                                    </h3>
                                    <p className="text-xs text-on-surface/60">
                                        {t('Poids :weight %', { weight: criterion.weight })}
                                    </p>
                                    <p className="mt-1 text-xs text-on-surface/70">
                                        {t(
                                            'Répartissez :pool points. Chaque note est un entier de 0 à :max.',
                                            {
                                                pool: criterion.pool,
                                                max: criterion.max_score,
                                            },
                                        )}
                                    </p>
                                </div>
                                <p
                                    className={`shrink-0 text-sm font-semibold ${
                                        left < 0 ? 'text-red-600' : 'text-secondary'
                                    }`}
                                >
                                    {t('Points restants : :count', { count: left })}
                                </p>
                            </div>

                            {targets.map((target) => (
                                <ScoreSlider
                                    key={`${criterion.id}-${target.id}`}
                                    id={`criterion-${criterion.id}-target-${target.id}`}
                                    label={target.label}
                                    max={criterion.max_score}
                                    value={scoreOf(target, criterion.id)}
                                    onChange={(value) =>
                                        setScore(target.id, criterion.id, value)
                                    }
                                    disabled={target.read_only}
                                />
                            ))}
                        </div>
                    );
                })}

                {budgetError && (
                    <p className="text-sm text-red-600">{budgetError}</p>
                )}

                {overBudget && (
                    <p className="text-sm text-red-600">
                        {t(
                            "Budget dépassé : baissez des notes avant d'enregistrer.",
                        )}
                    </p>
                )}

                {!readOnly && (
                    <PrimaryButton
                        disabled={processing || overBudget}
                        className="w-full justify-center"
                    >
                        {t("Enregistrer l'évaluation")}
                    </PrimaryButton>
                )}

                {readOnly && (
                    <p className="text-center text-sm text-tertiary">
                        {t('Évaluation déjà enregistrée.')}
                    </p>
                )}
            </form>
        </StudentLayout>
    );
}
