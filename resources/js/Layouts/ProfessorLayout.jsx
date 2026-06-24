import Dropdown from '@/Components/Dropdown';
import { Link, usePage } from '@inertiajs/react';

export default function ProfessorLayout({ children, title }) {
    const { auth } = usePage().props;

    return (
        <div className="min-h-screen bg-surface">
            <header className="border-b border-primary-container/15 bg-white">
                <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
                    <div>
                        <Link href={route('dashboard')} className="text-xs font-semibold uppercase tracking-wide text-secondary">
                            StudentLink · Professeur
                        </Link>
                        {title && (
                            <h1 className="text-xl font-semibold text-on-surface">{title}</h1>
                        )}
                    </div>
                    <Dropdown>
                        <Dropdown.Trigger>
                            <span className="inline-flex rounded-md">
                                <button
                                    type="button"
                                    className="inline-flex items-center rounded-studentlink border border-outline-variant/40 bg-white px-3 py-2 text-sm font-medium text-on-surface"
                                >
                                    {auth.user.name}
                                </button>
                            </span>
                        </Dropdown.Trigger>
                        <Dropdown.Content>
                            <Dropdown.Link href={route('profile.edit')}>
                                Profil
                            </Dropdown.Link>
                            <Dropdown.Link href={route('logout')} method="post" as="button">
                                Déconnexion
                            </Dropdown.Link>
                        </Dropdown.Content>
                    </Dropdown>
                </div>
            </header>

            <main className="mx-auto max-w-6xl px-4 py-6">{children}</main>
        </div>
    );
}
