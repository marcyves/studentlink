import FlashMessage from '@/Components/FlashMessage';
import Icon from '@/Components/Icon';
import StudentLayout from '@/Layouts/StudentLayout';
import { Head, Link } from '@inertiajs/react';

function EvaluationCard({ evaluation }) {
    const isInter = evaluation.type === 'inter';

    return (
        <Link
            href={route('student.evaluations.show', evaluation.id)}
            className="block rounded-studentlink border border-primary-container/20 bg-card p-4 transition hover:border-secondary/40"
        >
            <div className="flex items-start justify-between gap-3">
                <div>
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
                    <h3 className="mt-2 font-semibold text-on-surface">
                        {evaluation.target_label}
                    </h3>
                    <p className="text-sm text-on-surface/60">
                        {evaluation.project.title}
                    </p>
                </div>
                <span
                    className={`shrink-0 rounded-full px-2 py-1 text-xs font-medium ${
                        evaluation.status === 'pending'
                            ? 'bg-secondary/10 text-secondary'
                            : 'bg-tertiary-container/20 text-tertiary'
                    }`}
                >
                    {evaluation.status_label}
                </span>
            </div>
            {evaluation.project.ends_at && (
                <p className="mt-3 text-xs text-on-surface/50">
                    Échéance : {evaluation.project.ends_at}
                </p>
            )}
        </Link>
    );
}

export default function Index({ pending, completed, stats }) {
    return (
        <StudentLayout title="Évaluations par les pairs">
            <Head title="Évaluations" />
            <FlashMessage />

            <div className="mb-6 grid grid-cols-2 gap-3">
                <div className="rounded-studentlink border border-secondary/20 bg-card p-4">
                    <p className="text-2xl font-bold text-secondary">{stats.pending}</p>
                    <p className="text-xs text-on-surface/60">À faire</p>
                </div>
                <div className="rounded-studentlink border border-tertiary/20 bg-card p-4">
                    <p className="text-2xl font-bold text-tertiary">{stats.completed}</p>
                    <p className="text-xs text-on-surface/60">Terminées</p>
                </div>
            </div>

            <section className="mb-8 space-y-3">
                <h2 className="flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-on-surface/50">
                    <Icon name="pending_actions" className="text-base" />
                    À compléter
                </h2>
                {pending.length === 0 ? (
                    <p className="rounded-studentlink border border-dashed border-outline-variant/50 p-6 text-center text-sm text-on-surface/60">
                        Aucune évaluation en attente.
                    </p>
                ) : (
                    pending.map((evaluation) => (
                        <EvaluationCard key={evaluation.id} evaluation={evaluation} />
                    ))
                )}
            </section>

            {completed.length > 0 && (
                <section className="space-y-3">
                    <h2 className="flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-on-surface/50">
                        <Icon name="check_circle" className="text-base" />
                        Terminées
                    </h2>
                    {completed.map((evaluation) => (
                        <EvaluationCard key={evaluation.id} evaluation={evaluation} />
                    ))}
                </section>
            )}
        </StudentLayout>
    );
}
