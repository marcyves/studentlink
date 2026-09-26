import { usePage } from '@inertiajs/react';

export const localeTags = {
    fr: 'fr-FR',
    en: 'en-GB',
    it: 'it-IT',
    es: 'es-ES',
};

export function useT() {
    const { translations = {} } = usePage().props;

    return (key, replacements = {}) => {
        const template = Object.prototype.hasOwnProperty.call(translations, key)
            ? translations[key]
            : key;

        return Object.entries(replacements).reduce(
            (value, [name, replacement]) =>
                value.split(`:${name}`).join(String(replacement ?? '')),
            template,
        );
    };
}
