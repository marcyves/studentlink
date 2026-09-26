import PrimaryButton from '@/Components/PrimaryButton';
import GuestLayout from '@/Layouts/GuestLayout';
import { useT } from '@/i18n';
import { Head, Link, useForm } from '@inertiajs/react';

export default function VerifyEmail({ status }) {
    const t = useT();
    const { post, processing } = useForm({});

    const submit = (e) => {
        e.preventDefault();

        post(route('verification.send'));
    };

    return (
        <GuestLayout>
            <Head title={t("Vérification de l'e-mail")} />

            <div className="mb-4 text-sm text-on-surface/70">
                {t("Avant de continuer, confirmez votre adresse e-mail avec le lien que nous venons d'envoyer. Sinon, nous pouvons en envoyer un autre.")}
            </div>

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-sm font-medium text-green-600">
                    {t('Un nouveau lien de vérification a été envoyé à votre adresse e-mail.')}
                </div>
            )}

            <form onSubmit={submit}>
                <div className="mt-4 flex items-center justify-between">
                    <PrimaryButton disabled={processing}>
                        {t("Renvoyer l'e-mail de vérification")}
                    </PrimaryButton>

                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        {t('Déconnexion')}
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}
