import Icon from '@/Components/Icon';
import AdminLayout from '@/Layouts/AdminLayout';
import { useT } from '@/i18n';
import { Head, Link } from '@inertiajs/react';

function locationLabel(row) {
    const parts = [row.city, row.country].filter(Boolean);

    return parts.length > 0 ? parts.join(', ') : '—';
}

function AccessChart({ success, blocked }) {
    const t = useT();
    const max = Math.max(success, blocked, 1);
    const rows = [
        {
            key: 'success',
            label: t('Accès réussis'),
            value: success,
            bar: 'bg-tertiary',
        },
        {
            key: 'blocked',
            label: t('Accès bloqués'),
            value: blocked,
            bar: 'bg-red-700',
        },
    ];

    return (
        <section
            className="mb-8 rounded-studentlink border border-primary-container/15 bg-card p-5"
            aria-label={t('Comparaison des accès réussis et bloqués')}
        >
            <h2 className="text-lg font-semibold text-on-surface">
                {t('Comparaison des accès réussis et bloqués')}
            </h2>
            <ul className="mt-4 space-y-4">
                {rows.map((row) => (
                    <li key={row.key}>
                        <div className="mb-1 flex items-baseline justify-between gap-3 text-sm">
                            <span className="font-medium text-on-surface">{row.label}</span>
                            <span className="tabular-nums text-on-surface/70">{row.value}</span>
                        </div>
                        <div className="h-3 overflow-hidden rounded-full bg-surface-container">
                            <div
                                className={`h-full rounded-full ${row.bar}`}
                                style={{ width: `${(row.value / max) * 100}%` }}
                            />
                        </div>
                    </li>
                ))}
            </ul>
        </section>
    );
}

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

export default function Connections({ attempts, chart }) {
    const t = useT();

    return (
        <AdminLayout title={t('Journal des connexions')}>
            <Head title={t('Statistiques de connexion')} />

            <p className="mb-6">
                <Link
                    href={route('admin.dashboard')}
                    className="inline-flex items-center gap-1 text-sm font-medium text-primary-container hover:underline"
                >
                    <Icon name="arrow_back" className="text-base" />
                    {t("Retour à l'administration")}
                </Link>
            </p>

            <p className="mb-6 max-w-3xl text-sm text-on-surface/70">
                {t('Ces journaux aident à repérer les tentatives d\'accès interdites. Le pays et la ville sont renseignés quand l\'adresse IP peut être localisée. Sinon, le lieu reste vide.')}
            </p>

            <AccessChart success={chart.success} blocked={chart.blocked} />

            <section>
                <h2 className="mb-4 text-lg font-semibold text-on-surface">
                    {t('Connexions')}
                </h2>

                {attempts.data.length === 0 ? (
                    <p className="rounded-studentlink border border-dashed border-primary-container/20 p-8 text-center text-sm text-on-surface/60">
                        {t('Aucune connexion enregistrée.')}
                    </p>
                ) : (
                    <div className="overflow-x-auto rounded-studentlink border border-primary-container/15 bg-card">
                        <table className="min-w-full text-left text-sm">
                            <thead className="border-b border-primary-container/10 text-xs uppercase tracking-wide text-on-surface/50">
                                <tr>
                                    <th className="px-4 py-3 font-semibold">{t('Date et heure')}</th>
                                    <th className="px-4 py-3 font-semibold">{t('Identifiant')}</th>
                                    <th className="px-4 py-3 font-semibold">{t('Adresse IP')}</th>
                                    <th className="px-4 py-3 font-semibold">{t('Lieu')}</th>
                                    <th className="px-4 py-3 font-semibold">{t('Résultat')}</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-primary-container/10">
                                {attempts.data.map((row) => (
                                    <tr key={row.id}>
                                        <td className="whitespace-nowrap px-4 py-3 text-on-surface">
                                            {row.occurred_at}
                                        </td>
                                        <td className="px-4 py-3 text-on-surface">
                                            {row.identifier || '—'}
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-3 text-on-surface/80">
                                            {row.ip_address || '—'}
                                        </td>
                                        <td className="px-4 py-3 text-on-surface/80">
                                            {locationLabel(row)}
                                        </td>
                                        <td className="px-4 py-3">
                                            <span
                                                className={`rounded-full px-2 py-1 text-xs font-medium ${
                                                    row.outcome === 'success'
                                                        ? 'bg-tertiary-container/20 text-tertiary'
                                                        : 'bg-red-100 text-red-700'
                                                }`}
                                            >
                                                {row.outcome_label}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <Pager page={attempts} />
            </section>
        </AdminLayout>
    );
}
