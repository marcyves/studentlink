import UserMenu from '@/Components/UserMenu';
import { Link } from '@inertiajs/react';

export default function AdminLayout({ children, title }) {
    return (
        <div className="min-h-screen bg-surface">
            <header className="border-b border-primary-container/15 bg-white">
                <div className="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-4">
                    <div>
                        <Link
                            href={route('admin.dashboard')}
                            className="text-xs font-semibold uppercase tracking-wide text-secondary"
                        >
                            StudentLink · Admin
                        </Link>
                        {title && (
                            <h1 className="text-xl font-semibold text-on-surface">
                                {title}
                            </h1>
                        )}
                    </div>
                    <UserMenu />
                </div>
            </header>

            <main className="mx-auto max-w-6xl px-4 py-6">{children}</main>
        </div>
    );
}
