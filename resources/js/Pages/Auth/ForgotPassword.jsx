import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { useT } from '@/i18n';
import { Head, useForm } from '@inertiajs/react';

export default function ForgotPassword({ status }) {
    const t = useT();
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('password.email'));
    };

    return (
        <GuestLayout>
            <Head title={t('Mot de passe oublié')} />

            <div className="mb-4 text-sm text-on-surface/70">
                {t('Indiquez votre adresse e-mail. Nous vous enverrons un lien pour choisir un nouveau mot de passe.')}
            </div>

            {status && (
                <div className="mb-4 text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <form onSubmit={submit}>
                <TextInput
                    id="email"
                    type="email"
                    name="email"
                    value={data.email}
                    className="mt-1 block w-full"
                    isFocused={true}
                    onChange={(e) => setData('email', e.target.value)}
                />

                <InputError message={errors.email} className="mt-2" />

                <div className="mt-4 flex items-center justify-end">
                    <PrimaryButton className="ms-4" disabled={processing}>
                        {t('Envoyer le lien')}
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
