import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';

export default function Dashboard() {
    const { auth } = usePage().props;
    const roleLabel =
        auth.user.role === 'professor' ? 'Professeur' : 'Étudiant';

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-on-surface">
                    Tableau de bord
                </h2>
            }
        >
            <Head title="Tableau de bord" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-studentlink border border-primary-container/20 bg-white shadow-sm">
                        <div className="border-b border-primary-container/10 bg-primary-container/5 px-6 py-4">
                            <p className="text-sm font-medium text-secondary">
                                StudentLink
                            </p>
                            <h3 className="text-lg font-semibold text-on-surface">
                                Bienvenue, {auth.user.name}
                            </h3>
                            <p className="text-sm text-on-surface/70">
                                Profil : {roleLabel}
                            </p>
                        </div>
                        <div className="space-y-3 p-6 text-sm text-on-surface/80">
                            <p>
                                MVP en cours — prochaines étapes : groupes,
                                évaluations par les pairs, grilles professeur.
                            </p>
                            <ul className="list-inside list-disc space-y-1">
                                <li>Tableau de bord étudiant</li>
                                <li>Évaluation inter / intra-groupe</li>
                                <li>Vue d&apos;ensemble professeur</li>
                                <li>Configuration des grilles</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
