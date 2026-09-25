import Dropdown from '@/Components/Dropdown';
import Icon from '@/Components/Icon';
import ThemeToggle from '@/Components/ThemeToggle';
import { usePage } from '@inertiajs/react';

export default function UserMenu() {
    const { auth } = usePage().props;
    const onProfile = route().current('profile.edit');

    const linkClass =
        'block w-full px-4 py-2.5 text-start text-sm text-on-surface transition hover:bg-surface-container';

    return (
        <div className="flex items-center gap-2">
            <ThemeToggle />
            <Dropdown>
            <Dropdown.Trigger>
                <button
                    type="button"
                    className="inline-flex max-w-[10rem] items-center gap-1 rounded-studentlink border border-primary-container/20 bg-card px-3 py-2 text-sm font-medium text-on-surface sm:max-w-none"
                >
                    <span className="truncate">{auth.user.name}</span>
                    <Icon
                        name="expand_more"
                        className="shrink-0 text-base text-on-surface/50"
                    />
                </button>
            </Dropdown.Trigger>

            <Dropdown.Content
                contentClasses="overflow-hidden rounded-studentlink border border-primary-container/10 bg-card py-1 shadow-lg"
            >
                {!onProfile && (
                    <Dropdown.Link href={route('profile.edit')} className={linkClass}>
                        Profil
                    </Dropdown.Link>
                )}
                <Dropdown.Link
                    href={route('logout')}
                    method="post"
                    as="button"
                    className={`${linkClass} text-red-600 hover:bg-red-50`}
                >
                    Déconnexion
                </Dropdown.Link>
            </Dropdown.Content>
            </Dropdown>
        </div>
    );
}
