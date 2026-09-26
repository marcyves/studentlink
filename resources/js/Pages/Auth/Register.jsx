import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { useT } from '@/i18n';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Register({ registrationHint }) {
    const t = useT();
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title={t('Inscription étudiant')} />

            <p className="mb-4 text-sm text-on-surface/60">
                {t('Inscription réservée aux étudiants.')}{' '}
                {registrationHint
                    ? t('Adresse institutionnelle requise (:hint).', { hint: registrationHint })
                    : t('Utilisez l’adresse de votre établissement.')}
            </p>

            <form onSubmit={submit}>
                <div>
                    <InputLabel htmlFor="name" value={t('Nom')} />

                    <TextInput
                        id="name"
                        name="name"
                        value={data.name}
                        className="mt-1 block w-full"
                        autoComplete="name"
                        isFocused={true}
                        onChange={(e) => setData('name', e.target.value)}
                        required
                    />

                    <InputError message={errors.name} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="email" value={t('E-mail institutionnel')} />

                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full"
                        autoComplete="username"
                        onChange={(e) => setData('email', e.target.value)}
                        required
                    />

                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="password" value={t('Mot de passe')} />

                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        onChange={(e) => setData('password', e.target.value)}
                        required
                    />

                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel
                        htmlFor="password_confirmation"
                        value={t('Confirmer le mot de passe')}
                    />

                    <TextInput
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        onChange={(e) =>
                            setData('password_confirmation', e.target.value)
                        }
                        required
                    />

                    <InputError
                        message={errors.password_confirmation}
                        className="mt-2"
                    />
                </div>

                <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <Link
                        href={route('login')}
                        className="text-sm text-on-surface/60 underline hover:text-on-surface"
                    >
                        {t('Déjà inscrit ?')}
                    </Link>

                    <PrimaryButton disabled={processing}>
                        {t("S'inscrire")}
                    </PrimaryButton>
                </div>

                <p className="mt-4 text-center text-xs text-on-surface/50">
                    {t('Enseignant ?')}{' '}
                    <Link
                        href="/#professor-access"
                        className="text-primary-container underline"
                    >
                        {t('Demandez un accès')}
                    </Link>
                </p>
            </form>
        </GuestLayout>
    );
}
