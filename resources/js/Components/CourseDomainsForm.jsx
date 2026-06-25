import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function CourseDomainsForm({ course }) {
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
        <form
            onSubmit={submit}
            className="mb-6 rounded-studentlink border border-primary-container/15 bg-white p-4"
        >
            <h3 className="text-sm font-semibold text-on-surface">
                Domaines e-mail étudiants autorisés
            </h3>
            <p className="mt-1 text-xs text-on-surface/60">
                Laissez vide pour utiliser le domaine du professeur (
                {course.default_professor_domain
                    ? `@${course.default_professor_domain}`
                    : '—'}
                ). Domaines effectifs actuellement : {effectiveLabel}.
            </p>

            <div className="mt-3">
                <InputLabel
                    htmlFor={`domains-${course.id}`}
                    value="Domaines supplémentaires (séparés par des virgules)"
                />
                <TextInput
                    id={`domains-${course.id}`}
                    value={domainsText}
                    onChange={(e) => setDomainsText(e.target.value)}
                    placeholder={placeholder}
                    className="mt-1 block w-full"
                />
                <InputError
                    message={errors?.allowed_email_domains}
                    className="mt-2"
                />
            </div>

            <PrimaryButton className="mt-3" disabled={processing}>
                Enregistrer les domaines
            </PrimaryButton>
        </form>
    );
}
