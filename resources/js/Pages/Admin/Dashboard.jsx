import DangerButton from '@/Components/DangerButton';
import FlashMessage from '@/Components/FlashMessage';
import Icon from '@/Components/Icon';
import PrimaryButton from '@/Components/PrimaryButton';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

function statusClass(status) {
    if (status === 'pending') {
        return 'bg-amber-100 text-amber-800';
    }
    if (status === 'accepted') {
        return 'bg-tertiary-container/20 text-tertiary';
    }
    return 'bg-red-100 text-red-700';
}

function AccessRequestCard({ request }) {
    return (
        <li className="rounded-studentlink border border-primary-container/15 bg-white p-4">
            <div className="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <p className="font-semibold text-on-surface">{request.name}</p>
                    <p className="text-sm text-on-surface/70">
                        {request.email}
                        {request.institution && ` · ${request.institution}`}
                    </p>
                </div>
                <span
                    className={`rounded-full px-2 py-1 text-xs font-medium ${statusClass(request.status)}`}
                >
                    {request.status_label}
                </span>
            </div>
            {request.message && (
                <p className="mt-2 text-sm text-on-surface/60">{request.message}</p>
            )}
            <p className="mt-2 text-xs text-on-surface/40">{request.created_at}</p>

            {request.is_pending && (
                <div className="mt-4 flex flex-wrap gap-2">
                    <PrimaryButton
                        type="button"
                        onClick={() =>
                            router.post(
                                route('admin.access-requests.accept', request.id),
                                {},
                                { preserveScroll: true },
                            )
                        }
                    >
                        Accepter
                    </PrimaryButton>
                    <DangerButton
                        type="button"
                        onClick={() =>
                            router.post(
                                route('admin.access-requests.reject', request.id),
                                {},
                                { preserveScroll: true },
                            )
                        }
                    >
                        Rejeter
                    </DangerButton>
                </div>
            )}
        </li>
    );
}

function ProfessorCard({ professor }) {
    const [open, setOpen] = useState(false);

    return (
        <li className="rounded-studentlink border border-primary-container/15 bg-white">
            <button
                type="button"
                onClick={() => setOpen(!open)}
                className="flex w-full items-start justify-between gap-3 p-4 text-left"
            >
                <div>
                    <p className="font-semibold text-on-surface">{professor.name}</p>
                    <p className="text-sm text-on-surface/70">{professor.email}</p>
                    <p className="mt-1 text-xs text-on-surface/50">
                        Domaine par défaut : @{professor.default_domain ?? '—'} ·{' '}
                        {professor.courses_count} cours · {professor.students_count}{' '}
                        étudiant{professor.students_count > 1 ? 's' : ''}
                    </p>
                </div>
                <span className="text-sm text-primary-container">
                    {open ? '−' : '+'}
                </span>
            </button>

            {open && (
                <div className="border-t border-primary-container/10 px-4 pb-4">
                    {professor.courses.length > 0 && (
                        <div className="mt-3">
                            <h4 className="text-xs font-semibold uppercase tracking-wide text-secondary">
                                Cours
                            </h4>
                            <ul className="mt-2 space-y-2 text-sm">
                                {professor.courses.map((course) => (
                                    <li
                                        key={course.id}
                                        className="rounded-studentlink bg-surface-container/40 px-3 py-2"
                                    >
                                        <p className="font-medium text-on-surface">
                                            {course.title}
                                        </p>
                                        <p className="text-xs text-on-surface/60">
                                            Code {course.join_code} ·{' '}
                                            {course.students_count} inscrits · domaines
                                            effectifs :{' '}
                                            {course.effective_domains.length > 0
                                                ? course.effective_domains
                                                      .map((d) => `@${d}`)
                                                      .join(', ')
                                                : '—'}
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}

                    {professor.students.length > 0 ? (
                        <div className="mt-4">
                            <h4 className="text-xs font-semibold uppercase tracking-wide text-secondary">
                                Étudiants
                            </h4>
                            <ul className="mt-2 divide-y divide-primary-container/10 rounded-studentlink border border-primary-container/10">
                                {professor.students.map((student) => (
                                    <li
                                        key={`${student.id}-${student.course}`}
                                        className="flex flex-wrap justify-between gap-2 px-3 py-2 text-sm"
                                    >
                                        <span className="text-on-surface">
                                            {student.name}
                                        </span>
                                        <span className="text-on-surface/60">
                                            {student.email} · {student.course}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ) : (
                        <p className="mt-3 text-sm text-on-surface/50">
                            Aucun étudiant inscrit pour le moment.
                        </p>
                    )}
                </div>
            )}
        </li>
    );
}

export default function Dashboard({
    professors,
    accessRequests,
    adminEmail,
}) {
    const pendingCount = accessRequests.filter((r) => r.is_pending).length;

    return (
        <AdminLayout title="Administration">
            <Head title="Administration" />
            <FlashMessage />

            <div className="mb-6 flex justify-end">
                <Link
                    href={route('admin.settings.edit')}
                    className="inline-flex items-center justify-center rounded-studentlink border border-primary-container/25 bg-white p-2.5 text-primary-container transition hover:bg-surface-container/40"
                    aria-label="Paramètres"
                    title="Paramètres"
                >
                    <Icon name="settings" className="text-xl" />
                </Link>
            </div>

            <section className="mb-8">
                <h2 className="mb-2 text-lg font-semibold text-on-surface">
                    Demandes reçues ({accessRequests.length}
                    {pendingCount > 0 && ` · ${pendingCount} en attente`})
                </h2>
                <p className="mb-4 text-sm text-on-surface/60">
                    Accepter crée le compte professeur et envoie un e-mail de
                    définition de mot de passe. Notifications copiées vers{' '}
                    <strong className="text-on-surface">{adminEmail}</strong>.
                </p>

                {accessRequests.length === 0 ? (
                    <p className="rounded-studentlink border border-dashed border-primary-container/20 p-8 text-center text-sm text-on-surface/60">
                        Aucune demande pour le moment.
                    </p>
                ) : (
                    <ul className="space-y-3">
                        {accessRequests.map((request) => (
                            <AccessRequestCard
                                key={request.id}
                                request={request}
                            />
                        ))}
                    </ul>
                )}
            </section>

            <section className="mb-8">
                <h2 className="mb-4 text-lg font-semibold text-on-surface">
                    Professeurs ({professors.length})
                </h2>

                {professors.length === 0 ? (
                    <p className="rounded-studentlink border border-dashed border-primary-container/20 p-8 text-center text-sm text-on-surface/60">
                        Aucun professeur inscrit.
                    </p>
                ) : (
                    <ul className="space-y-3">
                        {professors.map((professor) => (
                            <ProfessorCard
                                key={professor.id}
                                professor={professor}
                            />
                        ))}
                    </ul>
                )}
            </section>
        </AdminLayout>
    );
}
