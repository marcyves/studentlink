import Icon from '@/Components/Icon';
import { useState } from 'react';

function isDark() {
    return document.documentElement.classList.contains('dark');
}

export default function ThemeToggle() {
    const [dark, setDark] = useState(isDark);

    const toggle = () => {
        const next = !isDark();
        document.documentElement.classList.toggle('dark', next);
        localStorage.setItem('studentlink-theme', next ? 'dark' : 'light');
        setDark(next);
    };

    return (
        <button
            type="button"
            onClick={toggle}
            title={dark ? 'Mode clair' : 'Mode sombre'}
            aria-label={dark ? 'Mode clair' : 'Mode sombre'}
            className="inline-flex h-9 w-9 items-center justify-center rounded-studentlink border border-primary-container/20 bg-card text-on-surface transition hover:bg-surface-container"
        >
            <Icon name={dark ? 'light_mode' : 'dark_mode'} className="text-xl" />
        </button>
    );
}
