import Checkbox from '@/Components/Checkbox';
import FlashMessage from '@/Components/FlashMessage';
import Icon from '@/Components/Icon';
import PrimaryButton from '@/Components/PrimaryButton';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Settings({ settings }) {
    const { data, setData, patch, processing } = useForm({
        require_registration_domain: settings.require_registration_domain,
    });

    const saveSettings = (e) => {
        e.preventDefault();
        patch(route('admin.settings.update'), { preserveScroll: true });
    };

    return (
        <AdminLayout title="Paramètres">
            <Head title="Paramètres · Admin" />
            <FlashMessage />

            <p className="mb-6">
                <Link
                    href={route('admin.dashboard')}
                    className="inline-flex items-center gap-1 text-sm font-medium text-primary-container hover:underline"
                >
                    <Icon name="arrow_back" className="text-base" />
                    Retour à l&apos;administration
                </Link>
            </p>

            <section className="rounded-studentlink border border-primary-container/15 bg-white p-5">
                <h2 className="text-lg font-semibold text-on-surface">
                    Inscription étudiante
                </h2>
                <p className="mt-1 text-sm text-on-surface/60">
                    Si activé, seules les adresses dont le domaine correspond à
                    un cours sont acceptées. Sans domaines explicites sur un
                    cours, c&apos;est le domaine e-mail du professeur qui
                    s&apos;applique.
                </p>

                <form onSubmit={saveSettings} className="mt-4">
                    <label className="flex items-start gap-3">
                        <Checkbox
                            name="require_registration_domain"
                            checked={data.require_registration_domain}
                            onChange={(e) =>
                                setData(
                                    'require_registration_domain',
                                    e.target.checked,
                                )
                            }
                        />
                        <span className="text-sm text-on-surface">
                            Restreindre l&apos;inscription aux domaines e-mail
                            autorisés
                        </span>
                    </label>

                    <PrimaryButton className="mt-4" disabled={processing}>
                        Enregistrer
                    </PrimaryButton>
                </form>
            </section>
        </AdminLayout>
    );
}
