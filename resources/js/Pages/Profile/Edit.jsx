import ProfessorLayout from '@/Layouts/ProfessorLayout';
import StudentLayout from '@/Layouts/StudentLayout';
import { Head, usePage } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

function ProfileCard({ children }) {
    return (
        <div className="rounded-studentlink border border-primary-container/15 bg-white p-5 shadow-sm md:p-6">
            {children}
        </div>
    );
}

export default function Edit({ mustVerifyEmail, status }) {
    const { auth } = usePage().props;
    const Layout =
        auth.user.role === 'professor' ? ProfessorLayout : StudentLayout;

    return (
        <Layout title="Mon profil">
            <Head title="Profil" />

            <div className="space-y-4">
                <ProfileCard>
                    <UpdateProfileInformationForm
                        mustVerifyEmail={mustVerifyEmail}
                        status={status}
                    />
                </ProfileCard>

                <ProfileCard>
                    <UpdatePasswordForm />
                </ProfileCard>

                <ProfileCard>
                    <DeleteUserForm />
                </ProfileCard>
            </div>
        </Layout>
    );
}
