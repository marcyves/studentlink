import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { useT } from '@/i18n';
import { useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';

export default function DeleteUserForm({ className = '' }) {
    const t = useT();
    const [confirmingUserDeletion, setConfirmingUserDeletion] = useState(false);
    const passwordInput = useRef();

    const {
        data,
        setData,
        delete: destroy,
        processing,
        reset,
        errors,
        clearErrors,
    } = useForm({
        password: '',
    });

    const confirmUserDeletion = () => {
        setConfirmingUserDeletion(true);
    };

    const deleteUser = (e) => {
        e.preventDefault();

        destroy(route('profile.destroy'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: () => passwordInput.current.focus(),
            onFinish: () => reset(),
        });
    };

    const closeModal = () => {
        setConfirmingUserDeletion(false);

        clearErrors();
        reset();
    };

    return (
        <section className={`space-y-6 ${className}`}>
            <header>
                <h2 className="text-lg font-semibold text-on-surface">
                    {t('Supprimer le compte')}
                </h2>

                <p className="mt-1 text-sm text-on-surface/60">
                    {t('Cette action est définitive. Toutes vos données seront effacées.')}
                </p>
            </header>

            <DangerButton onClick={confirmUserDeletion}>
                {t('Supprimer mon compte')}
            </DangerButton>

            <Modal show={confirmingUserDeletion} onClose={closeModal}>
                <form onSubmit={deleteUser} className="p-6">
                    <h2 className="text-lg font-medium text-gray-900">
                        {t('Êtes-vous sûr de vouloir supprimer votre compte ?')}
                    </h2>

                    <p className="mt-1 text-sm text-gray-600">
                        {t('Une fois le compte supprimé, toutes ses données le sont aussi. Saisissez votre mot de passe pour confirmer.')}
                    </p>

                    <div className="mt-6">
                        <InputLabel
                            htmlFor="password"
                            value={t('Mot de passe')}
                            className="sr-only"
                        />

                        <TextInput
                            id="password"
                            type="password"
                            name="password"
                            ref={passwordInput}
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                            className="mt-1 block w-3/4"
                            isFocused
                            placeholder={t('Mot de passe')}
                        />

                        <InputError
                            message={errors.password}
                            className="mt-2"
                        />
                    </div>

                    <div className="mt-6 flex justify-end">
                        <SecondaryButton onClick={closeModal}>
                            {t('Annuler')}
                        </SecondaryButton>

                        <DangerButton className="ms-3" disabled={processing}>
                            {t('Supprimer le compte')}
                        </DangerButton>
                    </div>
                </form>
            </Modal>
        </section>
    );
}
