import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { useT } from '@/i18n';
import { useForm } from '@inertiajs/react';

export default function ProfessorAccessForm() {
    const t = useT();
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        institution: '',
        message: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('professor-access.store'), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <form onSubmit={submit} className="space-y-4 text-left">
            <div>
                <InputLabel htmlFor="prof-name" value={t('Nom complet')} />
                <TextInput
                    id="prof-name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    className="mt-1 block w-full"
                    required
                />
                <InputError message={errors.name} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="prof-email" value={t('E-mail professionnel')} />
                <TextInput
                    id="prof-email"
                    type="email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    className="mt-1 block w-full"
                    required
                />
                <InputError message={errors.email} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="prof-institution" value={t('Établissement')} />
                <TextInput
                    id="prof-institution"
                    value={data.institution}
                    onChange={(e) => setData('institution', e.target.value)}
                    className="mt-1 block w-full"
                />
                <InputError message={errors.institution} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="prof-message" value={t('Message (optionnel)')} />
                <textarea
                    id="prof-message"
                    value={data.message}
                    onChange={(e) => setData('message', e.target.value)}
                    rows={3}
                    className="mt-1 block w-full rounded-studentlink border-gray-300 shadow-sm focus:border-primary-container focus:ring-primary-container"
                />
                <InputError message={errors.message} className="mt-2" />
            </div>

            <PrimaryButton disabled={processing} className="w-full justify-center">
                {t('Envoyer la demande')}
            </PrimaryButton>
        </form>
    );
}
