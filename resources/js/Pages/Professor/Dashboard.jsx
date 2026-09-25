import FlashMessage from '@/Components/FlashMessage';
import Icon from '@/Components/Icon';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import ProfessorLayout from '@/Layouts/ProfessorLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

function CreateCourseForm({ initiallyOpen = false }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        description: '',
        code: '',
        join_code: '',
    });
    const [open, setOpen] = useState(
        () =>
            initiallyOpen ||
            Boolean(
                errors.title ||
                    errors.description ||
                    errors.code ||
                    errors.join_code,
            ),
    );

    const submit = (e) => {
        e.preventDefault();

        post(route('professor.courses.store'), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    };

    if (!open) {
        return (
            <div className="mb-8">
                <button
                    type="button"
                    onClick={() => setOpen(true)}
                    className="inline-flex items-center gap-2 rounded-studentlink bg-primary-container px-4 py-2 text-sm font-medium text-white"
                >
                    <Icon name="add" className="text-base" />
                    Nouveau cours
                </button>
            </div>
        );
    }

    return (
        <form
            onSubmit={submit}
            className="mb-8 space-y-4 rounded-studentlink border border-primary-container/20 bg-white p-4"
        >
            <div>
                <h2 className="text-sm font-semibold text-on-surface">Nouveau cours</h2>
                <p className="mt-1 text-xs text-on-surface/60">
                    Les étudiants rejoignent le cours avec le code d'inscription.
                </p>
            </div>

            <div>
                <InputLabel htmlFor="course-title" value="Titre" />
                <TextInput
                    id="course-title"
                    value={data.title}
                    onChange={(e) => setData('title', e.target.value)}
                    className="mt-1 block w-full"
                    required
                />
                <InputError message={errors.title} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="course-description" value="Description" />
                <textarea
                    id="course-description"
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    rows={3}
                    className="mt-1 block w-full rounded-studentlink border-gray-300 shadow-sm focus:border-primary-container focus:ring-primary-container"
                />
                <InputError message={errors.description} className="mt-2" />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel htmlFor="course-code" value="Code du cours" />
                    <TextInput
                        id="course-code"
                        value={data.code}
                        onChange={(e) => setData('code', e.target.value)}
                        placeholder="MPWD-2026"
                        className="mt-1 block w-full"
                        required
                    />
                    <InputError message={errors.code} className="mt-2" />
                </div>
                <div>
                    <InputLabel htmlFor="course-join-code" value="Code d'inscription" />
                    <TextInput
                        id="course-join-code"
                        value={data.join_code}
                        onChange={(e) => setData('join_code', e.target.value)}
                        placeholder="JOIN2026"
                        className="mt-1 block w-full"
                        required
                    />
                    <InputError message={errors.join_code} className="mt-2" />
                </div>
            </div>

            <div className="flex items-center gap-3">
                <PrimaryButton disabled={processing}>Créer le cours</PrimaryButton>
                {!initiallyOpen && (
                    <button
                        type="button"
                        onClick={() => setOpen(false)}
                        className="text-sm text-on-surface/60"
                    >
                        Annuler
                    </button>
                )}
            </div>
        </form>
    );
}

function CreateProjectForm({ course, initiallyOpen = false }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        description: '',
        starts_at: '',
        ends_at: '',
    });
    const [open, setOpen] = useState(
        () => initiallyOpen || Boolean(errors.starts_at || errors.ends_at),
    );

    const submit = (e) => {
        e.preventDefault();
        post(route('professor.courses.projects.store', course.id), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    };

    if (!open) {
        return (
            <button
                type="button"
                onClick={() => setOpen(true)}
                className="mb-4 inline-flex items-center gap-2 rounded-studentlink border border-primary-container/30 bg-white px-3 py-2 text-sm font-medium text-primary-container"
            >
                <Icon name="add" className="text-base" />
                Nouveau projet
            </button>
        );
    }

    return (
        <form
            onSubmit={submit}
            className="mb-4 space-y-3 rounded-studentlink border border-dashed border-primary-container/30 bg-white p-4"
        >
            <h3 className="text-sm font-semibold text-on-surface">Nouveau projet</h3>

            <div>
                <InputLabel htmlFor={`project-title-${course.id}`} value="Titre" />
                <TextInput
                    id={`project-title-${course.id}`}
                    value={data.title}
                    onChange={(e) => setData('title', e.target.value)}
                    className="mt-1 block w-full"
                    required
                />
                <InputError message={errors.title} className="mt-2" />
            </div>

            <div>
                <InputLabel
                    htmlFor={`project-description-${course.id}`}
                    value="Description"
                />
                <textarea
                    id={`project-description-${course.id}`}
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    rows={3}
                    className="mt-1 block w-full rounded-studentlink border-gray-300 shadow-sm focus:border-primary-container focus:ring-primary-container"
                />
                <InputError message={errors.description} className="mt-2" />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel htmlFor={`project-start-${course.id}`} value="Début" />
                    <TextInput
                        id={`project-start-${course.id}`}
                        type="date"
                        value={data.starts_at}
                        onChange={(e) => setData('starts_at', e.target.value)}
                        className="mt-1 block w-full"
                        required
                    />
                    <InputError message={errors.starts_at} className="mt-2" />
                </div>
                <div>
                    <InputLabel htmlFor={`project-end-${course.id}`} value="Fin" />
                    <TextInput
                        id={`project-end-${course.id}`}
                        type="date"
                        value={data.ends_at}
                        onChange={(e) => setData('ends_at', e.target.value)}
                        className="mt-1 block w-full"
                        required
                    />
                    <InputError message={errors.ends_at} className="mt-2" />
                </div>
            </div>

            <div className="flex items-center gap-3">
                <PrimaryButton disabled={processing}>Créer le projet</PrimaryButton>
                {!initiallyOpen && (
                    <button
                        type="button"
                        onClick={() => setOpen(false)}
                        className="text-sm text-on-surface/60"
                    >
                        Annuler
                    </button>
                )}
            </div>
        </form>
    );
}

function projectPeriod(project) {
    if (project.starts_at && project.ends_at) {
        return `Du ${project.starts_at} au ${project.ends_at}`;
    }

    return `Échéance ${project.ends_at ?? '—'}`;
}

export default function Dashboard({ courses }) {
    return (
        <ProfessorLayout title="Vue d'ensemble">
            <Head title="Vue d'ensemble professeur" />
            <FlashMessage />

            {courses.length === 0 && (
                <p className="mb-4 text-sm text-on-surface/70">
                    Aucun cours pour le moment. Créez le vôtre pour que les étudiants
                    puissent s'inscrire.
                </p>
            )}

            <CreateCourseForm initiallyOpen={courses.length === 0} />

            {courses.map((course) => (
                <section key={course.id} className="mb-8">
                    <div className="mb-4 flex flex-wrap items-end justify-between gap-2">
                        <div>
                            <h2 className="text-lg font-semibold text-on-surface">
                                {course.title}
                            </h2>
                            {course.description && (
                                <p className="mt-1 max-w-2xl text-sm text-on-surface/70">
                                    {course.description}
                                </p>
                            )}
                            <p className="text-sm text-on-surface/60">
                                {course.code} · {course.students_count} étudiants ·
                                code inscription :{' '}
                                <strong>{course.join_code}</strong>
                            </p>
                        </div>
                        <div className="flex gap-4 text-sm">
                            <span>
                                <strong>{course.stats.groups}</strong> groupes
                            </span>
                            <span>
                                <strong>{course.stats.submitted}</strong> rendus
                            </span>
                        </div>
                    </div>

                    <CreateProjectForm
                        course={course}
                        initiallyOpen={course.projects.length === 0}
                    />

                    {course.projects.map((project) => (
                        <div
                            key={project.id}
                            className="mb-4 overflow-hidden rounded-studentlink border border-primary-container/20 bg-white"
                        >
                            <div className="flex flex-wrap items-center justify-between gap-2 border-b border-primary-container/10 bg-primary-container/5 px-4 py-3">
                                <div>
                                    <h3 className="font-medium text-on-surface">
                                        {project.title}
                                    </h3>
                                    {project.description && (
                                        <p className="mt-1 text-sm text-on-surface/70">
                                            {project.description}
                                        </p>
                                    )}
                                    <p className="text-xs text-on-surface/60">
                                        {projectPeriod(project)} · {project.groups_count}{' '}
                                        groupes
                                    </p>
                                </div>
                                <Link
                                    href={route('professor.rubrics.edit', project.id)}
                                    className="inline-flex items-center gap-1 rounded-studentlink bg-secondary px-3 py-2 text-sm font-medium text-white"
                                >
                                    <Icon name="tune" className="text-base" />
                                    Grille
                                    {project.rubric && (
                                        <span className="opacity-80">
                                            ({project.rubric.criteria_count} critères)
                                        </span>
                                    )}
                                </Link>
                                <a
                                    href={route('professor.grades.export', project.id)}
                                    className="inline-flex items-center gap-1 rounded-studentlink border border-primary-container/30 bg-white px-3 py-2 text-sm font-medium text-primary-container"
                                >
                                    <Icon name="download" className="text-base" />
                                    Export CSV
                                </a>
                            </div>

                            <div className="divide-y divide-primary-container/10">
                                {project.groups.length === 0 ? (
                                    <p className="px-4 py-6 text-sm text-on-surface/50">
                                        Aucun groupe constitué.
                                    </p>
                                ) : (
                                    project.groups.map((group) => (
                                        <div
                                            key={group.id}
                                            className="flex items-center justify-between px-4 py-3 text-sm"
                                        >
                                            <div>
                                                <p className="font-medium text-on-surface">
                                                    {group.name}
                                                </p>
                                                <p className="text-on-surface/60">
                                                    {group.members_count} membres
                                                </p>
                                            </div>
                                            <span
                                                className={`rounded-full px-2 py-1 text-xs font-medium ${
                                                    group.submission_status ===
                                                    'submitted'
                                                        ? 'bg-tertiary-container/20 text-tertiary'
                                                        : 'bg-surface-container text-on-surface/70'
                                                }`}
                                            >
                                                {group.submission_label ?? '—'}
                                            </span>
                                        </div>
                                    ))
                                )}
                            </div>
                        </div>
                    ))}
                </section>
            ))}
        </ProfessorLayout>
    );
}
