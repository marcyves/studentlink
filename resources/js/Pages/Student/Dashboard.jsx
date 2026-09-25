import DeliverablePreview from '@/Components/DeliverablePreview';
import FlashMessage from '@/Components/FlashMessage';
import Icon from '@/Components/Icon';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import StudentLayout from '@/Layouts/StudentLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

function JoinCourseForm() {
    const { data, setData, post, processing, reset } = useForm({
        join_code: '',
    });

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                post(route('student.courses.join'), {
                    onSuccess: () => reset('join_code'),
                });
            }}
            className="flex gap-2"
        >
            <TextInput
                value={data.join_code}
                onChange={(e) => setData('join_code', e.target.value)}
                placeholder="Code cours"
                className="flex-1"
            />
            <PrimaryButton disabled={processing}>Rejoindre</PrimaryButton>
        </form>
    );
}

function CreateGroupForm({ projects }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, reset, errors } = useForm({
        project_id: projects[0]?.id ?? '',
        name: '',
    });

    if (!projects.length) {
        return null;
    }

    return (
        <div className="rounded-studentlink border border-primary-container/20 bg-card p-4">
            <button
                type="button"
                onClick={() => setOpen(!open)}
                className="flex w-full items-center justify-between text-left font-medium text-on-surface"
            >
                <span className="flex items-center gap-2">
                    <Icon name="group_add" className="text-primary-container" />
                    Créer un groupe
                </span>
                <Icon name={open ? 'expand_less' : 'expand_more'} />
            </button>
            {open && (
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        post(route('student.groups.store'), {
                            onSuccess: () => {
                                reset('name');
                                setOpen(false);
                            },
                        });
                    }}
                    className="mt-4 space-y-3"
                >
                    <select
                        value={data.project_id}
                        onChange={(e) => setData('project_id', e.target.value)}
                        className="w-full rounded-studentlink border-gray-300 shadow-sm focus:border-primary-container focus:ring-primary-container"
                    >
                        {projects.map((p) => (
                            <option key={p.id} value={p.id}>
                                {p.title}
                            </option>
                        ))}
                    </select>
                    <TextInput
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        placeholder="Nom du groupe"
                        required
                    />
                    {errors.name && (
                        <p className="text-sm text-red-600">{errors.name}</p>
                    )}
                    <PrimaryButton disabled={processing}>Créer</PrimaryButton>
                </form>
            )}
        </div>
    );
}

const deliverableHints = {
    none: "Aucun contenu n'est demandé. Confirmez simplement le rendu.",
    file: 'Déposez un fichier (10 Mo maximum).',
    link: "Indiquez l'adresse du livrable.",
    image: 'Déposez une image (8 Mo maximum).',
    video: 'Déposez une vidéo (50 Mo maximum).',
    youtube: "Collez l'URL d'une vidéo YouTube.",
};

function submitLabel(type, submitted) {
    if (submitted && type !== 'none') {
        return 'Remplacer le livrable';
    }

    return {
        none: 'Marquer comme rendu',
        file: 'Déposer le fichier',
        link: 'Enregistrer le lien',
        image: "Déposer l'image",
        video: 'Déposer la vidéo',
        youtube: 'Enregistrer la vidéo',
    }[type];
}

function GroupSubmissionForm({ group }) {
    const type = group.project.deliverable_type;
    const submitted = group.submission?.status === 'submitted';
    const uploads = type === 'file' || type === 'image' || type === 'video';
    const { data, setData, post, processing, errors } = useForm({
        url: '',
        file: null,
    });

    if (type === 'none' && submitted) {
        return null;
    }

    const submit = (e) => {
        e.preventDefault();
        post(route('student.groups.submission.store', group.id), {
            preserveScroll: true,
            forceFormData: uploads,
        });
    };

    return (
        <form onSubmit={submit} className="mt-4 space-y-3 border-t border-primary-container/10 pt-3">
            <p className="text-xs text-on-surface/60">{deliverableHints[type]}</p>

            {(type === 'link' || type === 'youtube') && (
                <div>
                    <InputLabel htmlFor={`deliverable-url-${group.id}`} value="Adresse" />
                    <TextInput
                        id={`deliverable-url-${group.id}`}
                        type="url"
                        value={data.url}
                        onChange={(e) => setData('url', e.target.value)}
                        placeholder={
                            type === 'youtube'
                                ? 'https://www.youtube.com/watch?v=...'
                                : 'https://'
                        }
                        className="mt-1 block w-full"
                        required
                    />
                    <InputError message={errors.url} className="mt-2" />
                </div>
            )}

            {uploads && (
                <div>
                    <InputLabel htmlFor={`deliverable-file-${group.id}`} value="Fichier" />
                    <input
                        id={`deliverable-file-${group.id}`}
                        type="file"
                        accept={
                            type === 'image'
                                ? 'image/jpeg,image/png,image/gif,image/webp,image/bmp'
                                : type === 'video'
                                  ? 'video/*'
                                  : undefined
                        }
                        onChange={(e) => setData('file', e.target.files?.[0] ?? null)}
                        className="mt-1 block w-full text-sm text-on-surface/80"
                        required
                    />
                    <InputError message={errors.file} className="mt-2" />
                </div>
            )}

            <PrimaryButton disabled={processing}>
                {submitLabel(type, submitted)}
            </PrimaryButton>
        </form>
    );
}

function JoinGroupForm() {
    const { data, setData, post, processing, reset } = useForm({
        invite_code: '',
    });

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                post(route('student.groups.join'), {
                    onSuccess: () => reset('invite_code'),
                });
            }}
            className="flex gap-2"
        >
            <TextInput
                value={data.invite_code}
                onChange={(e) => setData('invite_code', e.target.value)}
                placeholder="Code groupe"
                className="flex-1"
            />
            <PrimaryButton disabled={processing}>Rejoindre</PrimaryButton>
        </form>
    );
}

export default function Dashboard({ groups, enrolledCourses, stats }) {
    const allProjects = enrolledCourses.flatMap((c) => c.projects);

    return (
        <StudentLayout title="Tableau de bord">
            <Head title="Tableau de bord" />
            <FlashMessage />

            <div className="mb-6 grid grid-cols-2 gap-3">
                <div className="rounded-studentlink border border-primary-container/20 bg-card p-4">
                    <p className="text-2xl font-bold text-primary-container">
                        {stats.groups}
                    </p>
                    <p className="text-xs text-on-surface/60">Mes groupes</p>
                </div>
                <div className="rounded-studentlink border border-secondary/20 bg-card p-4">
                    <p className="text-2xl font-bold text-secondary">
                        {stats.pendingEvaluations}
                    </p>
                    <p className="text-xs text-on-surface/60">Évaluations à faire</p>
                </div>
            </div>

            <section className="mb-6 space-y-3">
                <h2 className="text-sm font-semibold uppercase tracking-wide text-on-surface/50">
                    Rejoindre un cours
                </h2>
                <JoinCourseForm />
                <p className="text-xs text-on-surface/50">
                    Démo : code <strong>JOIN2026</strong>
                </p>
            </section>

            <section className="mb-6 space-y-3">
                <h2 className="text-sm font-semibold uppercase tracking-wide text-on-surface/50">
                    Groupes
                </h2>
                <CreateGroupForm projects={allProjects} />
                <JoinGroupForm />
            </section>

            <section className="space-y-3">
                <div className="flex items-center justify-between">
                    <h2 className="text-sm font-semibold uppercase tracking-wide text-on-surface/50">
                        Mes groupes
                    </h2>
                    {stats.pendingEvaluations > 0 && (
                        <Link
                            href={route('student.evaluations.index')}
                            className="text-xs font-medium text-secondary"
                        >
                            {stats.pendingEvaluations} évaluation
                            {stats.pendingEvaluations > 1 ? 's' : ''} à faire →
                        </Link>
                    )}
                </div>
                {groups.length === 0 ? (
                    <p className="rounded-studentlink border border-dashed border-outline-variant/50 p-6 text-center text-sm text-on-surface/60">
                        Aucun groupe pour l&apos;instant.
                    </p>
                ) : (
                    groups.map((group) => (
                        <article
                            key={group.id}
                            className="rounded-studentlink border border-primary-container/20 bg-card p-4"
                        >
                            <div className="flex items-start justify-between gap-2">
                                <div>
                                    <h3 className="font-semibold text-on-surface">
                                        {group.name}
                                    </h3>
                                    <p className="text-sm text-on-surface/60">
                                        {group.project.title} · {group.project.course}
                                    </p>
                                    <p className="mt-1 text-xs text-on-surface/50">
                                        Livrable : {group.project.deliverable_label}
                                    </p>
                                </div>
                                <span className="rounded-full bg-primary-container/10 px-2 py-1 text-xs font-medium text-primary-container">
                                    {group.submission?.label ?? '—'}
                                </span>
                            </div>
                            <div className="mt-3 flex items-center justify-between text-xs text-on-surface/50">
                                <span>{group.members_count} membres</span>
                                <span>Code : {group.invite_code}</span>
                            </div>
                            {group.deliverable?.submitted && (
                                <div className="mt-3">
                                    <DeliverablePreview deliverable={group.deliverable} />
                                </div>
                            )}
                            <GroupSubmissionForm group={group} />
                        </article>
                    ))
                )}
            </section>
        </StudentLayout>
    );
}
