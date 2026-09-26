import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { useT } from '@/i18n';
import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';

function fieldError(errors, name) {
    if (!errors) {
        return undefined;
    }

    if (errors[name]) {
        return Array.isArray(errors[name]) ? errors[name][0] : errors[name];
    }

    const nested = Object.keys(errors).find((key) => key.startsWith(`${name}.`));

    if (!nested) {
        return undefined;
    }

    return Array.isArray(errors[nested]) ? errors[nested][0] : errors[nested];
}

export default function CourseDomainsForm({ course }) {
    const t = useT();
    const { errors } = usePage().props;
    const placeholder =
        course.default_professor_domain ?? 'ipag.fr, etu.ipag.fr';
    const effectiveLabel =
        (course.effective_email_domains ?? []).length > 0
            ? course.effective_email_domains.map((d) => `@${d}`).join(', ')
            : '—';

    const initial = (course.allowed_email_domains ?? []).join(', ');
    const [domainsText, setDomainsText] = useState(initial);
    const [processing, setProcessing] = useState(false);

    const submit = (e) => {
        e.preventDefault();

        const domains = domainsText
            .split(/[\s,;]+/)
            .map((d) => d.trim().replace(/^@+/, ''))
            .filter(Boolean);

        setProcessing(true);

        router.put(
            route('professor.courses.domains.update', course.id),
            { allowed_email_domains: domains },
            {
                preserveScroll: true,
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <form onSubmit={submit}>
            <h3 className="text-sm font-semibold text-on-surface">
                {course.title}
            </h3>
            <p className="mt-1 text-xs text-on-surface/60">
                {t(':code · laissez vide pour utiliser le domaine de votre e-mail (:domain). Domaines effectifs actuellement : :effective.', {
                    code: course.code,
                    domain: course.default_professor_domain
                        ? `@${course.default_professor_domain}`
                        : '—',
                    effective: effectiveLabel,
                })}
            </p>

            <div className="mt-3">
                <InputLabel
                    htmlFor={`domains-${course.id}`}
                    value={t('Domaines e-mail autorisés (séparés par des virgules)')}
                />
                <TextInput
                    id={`domains-${course.id}`}
                    value={domainsText}
                    onChange={(e) => setDomainsText(e.target.value)}
                    placeholder={placeholder}
                    className="mt-1 block w-full"
                />
                <InputError
                    message={fieldError(errors, 'allowed_email_domains')}
                    className="mt-2"
                />
            </div>

            <PrimaryButton className="mt-3" disabled={processing}>
                {t('Enregistrer les domaines')}
            </PrimaryButton>
        </form>
    );
}
