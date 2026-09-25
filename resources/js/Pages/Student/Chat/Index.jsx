import Icon from '@/Components/Icon';
import StudentLayout from '@/Layouts/StudentLayout';
import { Head, Link } from '@inertiajs/react';

export default function Index({ groups }) {
    return (
        <StudentLayout title="Chat de groupe">
            <Head title="Chat" />

            {groups.length === 0 ? (
                <p className="rounded-studentlink border border-dashed border-outline-variant/50 p-6 text-center text-sm text-on-surface/60">
                    Rejoignez un groupe pour accéder au chat.
                </p>
            ) : (
                <ul className="space-y-3">
                    {groups.map((group) => (
                        <li key={group.id}>
                            <Link
                                href={route('student.chat.show', group.id)}
                                className="flex items-center gap-3 rounded-studentlink border border-primary-container/20 bg-card p-4 transition hover:border-secondary/40"
                            >
                                <div className="flex h-10 w-10 items-center justify-center rounded-full bg-secondary/10 text-secondary">
                                    <Icon name="forum" />
                                </div>
                                <div className="min-w-0 flex-1">
                                    <p className="font-semibold text-on-surface">
                                        {group.name}
                                    </p>
                                    <p className="truncate text-xs text-on-surface/60">
                                        {group.project} · {group.course}
                                    </p>
                                    {group.last_message && (
                                        <p className="mt-1 truncate text-sm text-on-surface/50">
                                            {group.last_message}
                                        </p>
                                    )}
                                </div>
                                <Icon name="chevron_right" className="text-on-surface/40" />
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </StudentLayout>
    );
}
