import FlashMessage from '@/Components/FlashMessage';
import Icon from '@/Components/Icon';
import ProfessorLayout from '@/Layouts/ProfessorLayout';
import { Head, Link } from '@inertiajs/react';

export default function Dashboard({ courses }) {
    return (
        <ProfessorLayout title="Vue d'ensemble">
            <Head title="Vue d'ensemble professeur" />
            <FlashMessage />

            {courses.length === 0 ? (
                <p className="rounded-studentlink border border-dashed border-outline-variant/50 p-8 text-center text-on-surface/60">
                    Aucun cours. Lancez{' '}
                    <code className="text-sm">php artisan db:seed</code> pour la démo.
                </p>
            ) : (
                courses.map((course) => (
                    <section key={course.id} className="mb-8">
                        <div className="mb-4 flex flex-wrap items-end justify-between gap-2">
                            <div>
                                <h2 className="text-lg font-semibold text-on-surface">
                                    {course.title}
                                </h2>
                                <p className="text-sm text-on-surface/60">
                                    {course.code} · {course.students_count} étudiants ·
                                    code inscription :{' '}
                                    <strong>{course.join_code}</strong>
                                </p>
                            </div>
                            <div className="flex gap-4 text-sm">
                                <span>
                                    <strong>{course.stats.groups}</strong> groupes
                                </span>
                                <span>
                                    <strong>{course.stats.submitted}</strong> rendus
                                </span>
                            </div>
                        </div>

                        {course.projects.map((project) => (
                            <div
                                key={project.id}
                                className="mb-4 overflow-hidden rounded-studentlink border border-primary-container/20 bg-white"
                            >
                                <div className="flex flex-wrap items-center justify-between gap-2 border-b border-primary-container/10 bg-primary-container/5 px-4 py-3">
                                    <div>
                                        <h3 className="font-medium text-on-surface">
                                            {project.title}
                                        </h3>
                                        <p className="text-xs text-on-surface/60">
                                            Échéance {project.ends_at ?? '—'} ·{' '}
                                            {project.groups_count} groupes
                                        </p>
                                    </div>
                                    <Link
                                        href={route('professor.rubrics.edit', project.id)}
                                        className="inline-flex items-center gap-1 rounded-studentlink bg-secondary px-3 py-2 text-sm font-medium text-white"
                                    >
                                        <Icon name="tune" className="text-base" />
                                        Grille
                                        {project.rubric && (
                                            <span className="opacity-80">
                                                ({project.rubric.criteria_count} critères)
                                            </span>
                                        )}
                                    </Link>
                                </div>

                                <div className="divide-y divide-primary-container/10">
                                    {project.groups.length === 0 ? (
                                        <p className="px-4 py-6 text-sm text-on-surface/50">
                                            Aucun groupe constitué.
                                        </p>
                                    ) : (
                                        project.groups.map((group) => (
                                            <div
                                                key={group.id}
                                                className="flex items-center justify-between px-4 py-3 text-sm"
                                            >
                                                <div>
                                                    <p className="font-medium text-on-surface">
                                                        {group.name}
                                                    </p>
                                                    <p className="text-on-surface/60">
                                                        {group.members_count} membres
                                                    </p>
                                                </div>
                                                <span
                                                    className={`rounded-full px-2 py-1 text-xs font-medium ${
                                                        group.submission_status ===
                                                        'submitted'
                                                            ? 'bg-tertiary-container/20 text-tertiary'
                                                            : 'bg-surface-container text-on-surface/70'
                                                    }`}
                                                >
                                                    {group.submission_label ?? '—'}
                                                </span>
                                            </div>
                                        ))
                                    )}
                                </div>
                            </div>
                        ))}
                    </section>
                ))
            )}
        </ProfessorLayout>
    );
}
