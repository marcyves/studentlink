import Icon from '@/Components/Icon';
import UserMenu from '@/Components/UserMenu';
import { Link } from '@inertiajs/react';

const navItems = [
    { name: 'dashboard', icon: 'dashboard', label: 'Accueil' },
    { name: 'student.evaluations.index', icon: 'rate_review', label: 'Évaluations' },
    { name: 'student.chat.index', icon: 'forum', label: 'Chat' },
    { name: 'profile.edit', icon: 'person', label: 'Profil' },
];

export default function StudentLayout({ children, title }) {
    return (
        <div className="min-h-screen bg-surface pb-24">
            <header className="border-b border-primary-container/15 bg-white px-4 py-4">
                <div className="mx-auto flex max-w-lg items-start justify-between gap-3">
                    <div className="min-w-0">
                        <p className="text-xs font-semibold uppercase tracking-wide text-secondary">
                            StudentLink
                        </p>
                        {title && (
                            <h1 className="text-xl font-semibold text-on-surface">
                                {title}
                            </h1>
                        )}
                    </div>
                    <UserMenu />
                </div>
            </header>

            <main className="mx-auto max-w-lg px-4 py-4">{children}</main>

            <nav className="fixed bottom-0 left-0 right-0 border-t border-primary-container/15 bg-white">
                <div className="mx-auto flex max-w-lg justify-around py-2">
                    {navItems.map((item) => (
                        <Link
                            key={item.name}
                            href={route(item.name)}
                            className={`flex flex-col items-center gap-1 px-3 py-2 text-xs ${
                                route().current(item.name) ||
                                (item.name === 'student.evaluations.index' &&
                                    route().current('student.evaluations.*')) ||
                                (item.name === 'student.chat.index' &&
                                    route().current('student.chat.*'))
                                    ? 'font-semibold text-primary-container'
                                    : 'text-on-surface/60'
                            }`}
                        >
                            <Icon name={item.icon} className="text-xl" />
                            {item.label}
                        </Link>
                    ))}
                </div>
            </nav>
        </div>
    );
}
