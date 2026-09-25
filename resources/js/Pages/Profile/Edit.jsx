import CourseDomainsForm from '@/Components/CourseDomainsForm';
import FlashMessage from '@/Components/FlashMessage';
import AdminLayout from '@/Layouts/AdminLayout';
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

export default function Edit({ mustVerifyEmail, status, courses = [] }) {
    const { auth } = usePage().props;
    const Layout =
        auth.user.role === 'admin'
            ? AdminLayout
            : auth.user.role === 'professor'
              ? ProfessorLayout
              : StudentLayout;

    return (
        <Layout title="Mon profil">
            <Head title="Profil" />
            <FlashMessage />

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

                {auth.user.role === 'professor' && (
                    <ProfileCard>
                        <header>
                            <h2 className="text-lg font-semibold text-on-surface">
                                Domaines e-mail des cours
                            </h2>
                            <p className="mt-1 text-sm text-on-surface/60">
                                Chaque cours conserve ses domaines. Si aucun
                                domaine n'est renseigné, le domaine de votre
                                adresse e-mail s'applique.
                            </p>
                        </header>

                        {courses.length === 0 ? (
                            <p className="mt-4 text-sm text-on-surface/70">
                                Aucun cours pour le moment. Les domaines se
                                règlent ici dès qu'un cours existe.
                            </p>
                        ) : (
                            <div className="mt-6 space-y-6">
                                {courses.map((course) => (
                                    <CourseDomainsForm
                                        key={course.id}
                                        course={course}
                                    />
                                ))}
                            </div>
                        )}
                    </ProfileCard>
                )}

                <ProfileCard>
                    <DeleteUserForm />
                </ProfileCard>
            </div>
        </Layout>
    );
}
