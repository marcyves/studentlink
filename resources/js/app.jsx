import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

function applyDocumentLang(page) {
    const locale = page?.props?.locale;

    if (locale) {
        document.documentElement.lang = locale;
    }
}

router.on('navigate', (event) => applyDocumentLang(event.detail.page));
router.on('success', (event) => applyDocumentLang(event.detail.page));

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        ),
    setup({ el, App, props }) {
        applyDocumentLang(props.initialPage);

        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});
