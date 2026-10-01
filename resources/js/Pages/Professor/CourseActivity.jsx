import Icon from '@/Components/Icon';
import ProfessorLayout from '@/Layouts/ProfessorLayout';
import { useT } from '@/i18n';
import { Head, Link } from '@inertiajs/react';

function Pager({ page }) {
    const t = useT();

    if (!page.prev_page_url && !page.next_page_url) {
        return null;
    }

    return (
        <div className="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm">
            <p className="text-on-surface/60">
                {t(':from–:to sur :total', {
                    from: page.from ?? 0,
                    to: page.to ?? 0,
                    total: page.total ?? 0,
                })}
            </p>
            <div className="flex gap-3">
                {page.prev_page_url && (
                    <Link
                        href={page.prev_page_url}
                        className="font-medium text-primary-container hover:underline"
                        preserveScroll
                    >
                        {t('Page précédente')}
                    </Link>
                )}
                {page.next_page_url && (
                    <Link
                        href={page.next_page_url}
                        className="font-medium text-primary-container hover:underline"
                        preserveScroll
                    >
                        {t('Page suivante')}
                    </Link>
                )}
            </div>
        </div>
    );
}

export default function CourseActivity({ course, students, activities }) {
    const t = useT();

    return (
        <ProfessorLayout title={t('Activité de la classe')}>
            <Head title={t('Activité de la classe')} />

            <p className="mb-4">
                <Link
                    href={route('dashboard')}
                    className="inline-flex items-center gap-1 text-sm font-medium text-primary-container hover:underline"
                >
                    <Icon name="arrow_back" className="text-base" />
                    {t('Retour aux cours')}
                </Link>
            </p>

            <h2 className="text-lg font-semibold text-on-surface">{course.title}</h2>
            <p className="mt-1 mb-6 max-w-3xl text-sm text-on-surface/70">
                {t('Code :code', { code: course.code })}
                {' · '}
                {t('Suivez qui s\'est connecté et ce que les étudiants ont fait dans ce cours.')}
            </p>

            <section className="mb-8">
                <h3 className="mb-4 text-base font-semibold text-on-surface">
                    {t('Connexions des étudiants')}
                </h3>

                {students.length === 0 ? (
                    <p className="rounded-studentlink border border-dashed border-primary-container/20 p-8 text-center text-sm text-on-surface/60">
                        {t('Aucun étudiant inscrit à ce cours.')}
                    </p>
                ) : (
                    <div className="overflow-x-auto rounded-studentlink border border-primary-container/15 bg-card">
                        <table className="min-w-full text-left text-sm">
                            <thead className="border-b border-primary-container/10 text-xs uppercase tracking-wide text-on-surface/50">
                                <tr>
                                    <th className="px-4 py-3 font-semibold">{t('Étudiant')}</th>
                                    <th className="px-4 py-3 font-semibold">{t('Statut de connexion')}</th>
                                    <th className="px-4 py-3 font-semibold">{t('Dernière connexion')}</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-primary-container/10">
                                {students.map((student) => (
                                    <tr key={student.id}>
                                        <td className="px-4 py-3">
                                            <p className="font-medium text-on-surface">{student.name}</p>
                                            <p className="text-xs text-on-surface/60">{student.email}</p>
                                        </td>
                                        <td className="px-4 py-3">
                                            <span
                                                className={`rounded-full px-2 py-1 text-xs font-medium ${
                                                    student.has_logged_in
                                                        ? 'bg-tertiary-container/20 text-tertiary'
                                                        : 'bg-surface-container text-on-surface/70'
                                                }`}
                                            >
                                                {student.has_logged_in
                                                    ? t('Connecté')
                                                    : t('Jamais connecté')}
                                            </span>
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-3 text-on-surface/80">
                                            {student.last_login_at || '—'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </section>

            <section>
                <h3 className="mb-4 text-base font-semibold text-on-surface">
                    {t('Actions des étudiants')}
                </h3>

                {activities.data.length === 0 ? (
                    <p className="rounded-studentlink border border-dashed border-primary-container/20 p-8 text-center text-sm text-on-surface/60">
                        {t('Aucune activité enregistrée.')}
                    </p>
                ) : (
                    <div className="overflow-x-auto rounded-studentlink border border-primary-container/15 bg-card">
                        <table className="min-w-full text-left text-sm">
                            <thead className="border-b border-primary-container/10 text-xs uppercase tracking-wide text-on-surface/50">
                                <tr>
                                    <th className="px-4 py-3 font-semibold">{t('Date et heure')}</th>
                                    <th className="px-4 py-3 font-semibold">{t('Étudiant')}</th>
                                    <th className="px-4 py-3 font-semibold">{t('Action')}</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-primary-container/10">
                                {activities.data.map((row) => (
                                    <tr key={row.id}>
                                        <td className="whitespace-nowrap px-4 py-3 text-on-surface">
                                            {row.occurred_at}
                                        </td>
                                        <td className="px-4 py-3 text-on-surface">
                                            {row.student || '—'}
                                        </td>
                                        <td className="px-4 py-3 text-on-surface/80">
                                            {row.action_label}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <Pager page={activities} />
            </section>
        </ProfessorLayout>
    );
}
