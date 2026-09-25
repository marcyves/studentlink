import FlashMessage from '@/Components/FlashMessage';
import Icon from '@/Components/Icon';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import ProfessorLayout from '@/Layouts/ProfessorLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

function IconAction({ label, icon, onClick, disabled = false, tone = 'primary' }) {
    const toneClass =
        tone === 'danger'
            ? 'text-red-700 hover:bg-red-50'
            : 'text-primary-container hover:bg-primary-container/10';

    return (
        <button
            type="button"
            onClick={onClick}
            disabled={disabled}
            title={label}
            aria-label={label}
            className={`inline-flex h-9 w-9 items-center justify-center rounded-studentlink ${toneClass} disabled:opacity-40`}
        >
            <Icon name={icon} className="text-xl" />
        </button>
    );
}

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
            className="mb-8 space-y-4 rounded-studentlink border border-primary-container/20 bg-card p-4"
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

function DeliverableTypeSelect({ id, value, onChange, types }) {
    return (
        <select
            id={id}
            value={value}
            onChange={onChange}
            className="mt-1 block w-full rounded-studentlink border-gray-300 shadow-sm focus:border-primary-container focus:ring-primary-container"
            required
        >
            {types.map((type) => (
                <option key={type.value} value={type.value}>
                    {type.label}
                </option>
            ))}
        </select>
    );
}

function CreateProjectForm({ course, deliverableTypes, initiallyOpen = false }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        description: '',
        deliverable_type: 'none',
        starts_at: '',
        ends_at: '',
    });
    const [open, setOpen] = useState(
        () =>
            initiallyOpen ||
            Boolean(
                errors.starts_at ||
                    errors.ends_at ||
                    errors.deliverable_type,
            ),
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
                className="mb-4 inline-flex items-center gap-2 rounded-studentlink border border-primary-container/30 bg-card px-3 py-2 text-sm font-medium text-primary-container"
            >
                <Icon name="add" className="text-base" />
                Nouveau projet
            </button>
        );
    }

    return (
        <form
            onSubmit={submit}
            className="mb-4 space-y-4 rounded-studentlink border border-dashed border-primary-container/30 bg-card p-4"
        >
            <h3 className="text-sm font-semibold text-on-surface">Nouveau projet</h3>

            <div className="grid gap-4 lg:grid-cols-2 lg:gap-6">
                <div className="space-y-3">
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
                            className="mt-1 block w-full rounded-studentlink border-gray-300 shadow-sm focus:border-primary-container focus:ring-primary-container lg:min-h-48"
                        />
                        <InputError message={errors.description} className="mt-2" />
                    </div>
                </div>

                <div className="space-y-3">
                    <div>
                        <InputLabel
                            htmlFor={`project-deliverable-${course.id}`}
                            value="Type de livrable"
                        />
                        <DeliverableTypeSelect
                            id={`project-deliverable-${course.id}`}
                            value={data.deliverable_type}
                            types={deliverableTypes}
                            onChange={(e) => setData('deliverable_type', e.target.value)}
                        />
                        <InputError message={errors.deliverable_type} className="mt-2" />
                    </div>

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

function EditCourseForm({ course }) {
    const { data, setData, put, processing, errors } = useForm({
        title: course.title,
        description: course.description ?? '',
        code: course.code,
        join_code: course.join_code,
    });
    const [open, setOpen] = useState(
        () =>
            Boolean(
                errors.title ||
                    errors.description ||
                    errors.code ||
                    errors.join_code,
            ),
    );

    const submit = (e) => {
        e.preventDefault();
        put(route('professor.courses.update', course.id), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    };

    if (!open) {
        return (
            <IconAction label="Modifier" icon="edit" onClick={() => setOpen(true)} />
        );
    }

    return (
        <form
            onSubmit={submit}
            className="mt-3 w-full space-y-3 rounded-studentlink border border-primary-container/20 bg-card p-4"
        >
            <h3 className="text-sm font-semibold text-on-surface">Modifier le cours</h3>
            <div>
                <InputLabel htmlFor={`edit-course-title-${course.id}`} value="Titre" />
                <TextInput
                    id={`edit-course-title-${course.id}`}
                    value={data.title}
                    onChange={(e) => setData('title', e.target.value)}
                    className="mt-1 block w-full"
                    required
                />
                <InputError message={errors.title} className="mt-2" />
            </div>
            <div>
                <InputLabel
                    htmlFor={`edit-course-description-${course.id}`}
                    value="Description"
                />
                <textarea
                    id={`edit-course-description-${course.id}`}
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    rows={3}
                    className="mt-1 block w-full rounded-studentlink border-gray-300 shadow-sm focus:border-primary-container focus:ring-primary-container"
                />
                <InputError message={errors.description} className="mt-2" />
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel htmlFor={`edit-course-code-${course.id}`} value="Code du cours" />
                    <TextInput
                        id={`edit-course-code-${course.id}`}
                        value={data.code}
                        onChange={(e) => setData('code', e.target.value)}
                        className="mt-1 block w-full"
                        required
                    />
                    <InputError message={errors.code} className="mt-2" />
                </div>
                <div>
                    <InputLabel
                        htmlFor={`edit-course-join-${course.id}`}
                        value="Code d'inscription"
                    />
                    <TextInput
                        id={`edit-course-join-${course.id}`}
                        value={data.join_code}
                        onChange={(e) => setData('join_code', e.target.value)}
                        className="mt-1 block w-full"
                        required
                    />
                    <InputError message={errors.join_code} className="mt-2" />
                </div>
            </div>
            <div className="flex items-center gap-3">
                <PrimaryButton disabled={processing}>Enregistrer</PrimaryButton>
                <button
                    type="button"
                    onClick={() => setOpen(false)}
                    className="text-sm text-on-surface/60"
                >
                    Annuler
                </button>
            </div>
        </form>
    );
}

function DeleteCourseControl({ course }) {
    const { delete: destroy, processing, errors, transform } = useForm({
        delete_students: false,
    });
    const [open, setOpen] = useState(false);

    const remove = (deleteStudents) => {
        transform(() => ({ delete_students: deleteStudents }));
        destroy(route('professor.courses.destroy', course.id), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    };

    return (
        <>
            <IconAction
                label="Effacer"
                icon="delete"
                tone="danger"
                onClick={() => setOpen(true)}
                disabled={processing}
            />
            <Modal show={open} maxWidth="md" onClose={() => setOpen(false)}>
                <div className="p-6">
                    <h2 className="text-lg font-medium text-on-surface">
                        {course.is_empty ? 'Êtes-vous sûr ?' : 'Cours non vide'}
                    </h2>
                    {course.is_empty ? (
                        <p className="mt-2 text-sm text-on-surface/70">
                            Ce cours n&apos;a aucun projet. Faut-il aussi effacer
                            les étudiants inscrits ? Ceux qui sont aussi dans un
                            autre cours sont conservés.
                        </p>
                    ) : (
                        <p className="mt-2 text-sm text-on-surface/70">
                            Supprimez d&apos;abord les projets de ce cours. Rien
                            n&apos;a été effacé.
                        </p>
                    )}
                    <InputError message={errors.delete_students} className="mt-2" />
                    <div className="mt-6 flex flex-wrap justify-end gap-3">
                        <button
                            type="button"
                            onClick={() => setOpen(false)}
                            className="text-sm text-on-surface/60"
                        >
                            Annuler
                        </button>
                        {course.is_empty && (
                            <>
                                <button
                                    type="button"
                                    onClick={() => remove(false)}
                                    disabled={processing}
                                    className="rounded-studentlink border border-red-300 bg-card px-3 py-2 text-sm font-medium text-red-700"
                                >
                                    Effacer le cours seulement
                                </button>
                                <button
                                    type="button"
                                    onClick={() => remove(true)}
                                    disabled={processing}
                                    className="rounded-studentlink bg-red-700 px-3 py-2 text-sm font-medium text-white"
                                >
                                    Effacer le cours et les étudiants
                                </button>
                            </>
                        )}
                    </div>
                </div>
            </Modal>
        </>
    );
}

function EditProjectForm({ project, deliverableTypes }) {
    const { data, setData, put, processing, errors } = useForm({
        title: project.title,
        description: project.description ?? '',
        deliverable_type: project.deliverable_type,
        starts_at: project.starts_on,
        ends_at: project.ends_on,
    });
    const [open, setOpen] = useState(
        () =>
            Boolean(
                errors.title ||
                    errors.description ||
                    errors.deliverable_type ||
                    errors.starts_at ||
                    errors.ends_at,
            ),
    );

    const submit = (e) => {
        e.preventDefault();
        put(route('professor.projects.update', project.id), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    };

    if (!open) {
        return (
            <IconAction label="Modifier" icon="edit" onClick={() => setOpen(true)} />
        );
    }

    return (
        <form
            onSubmit={submit}
            className="mt-3 w-full space-y-3 rounded-studentlink border border-primary-container/20 bg-card p-4"
        >
            <h4 className="text-sm font-semibold text-on-surface">Modifier le projet</h4>
            <div>
                <InputLabel htmlFor={`edit-project-title-${project.id}`} value="Titre" />
                <TextInput
                    id={`edit-project-title-${project.id}`}
                    value={data.title}
                    onChange={(e) => setData('title', e.target.value)}
                    className="mt-1 block w-full"
                    required
                />
                <InputError message={errors.title} className="mt-2" />
            </div>
            <div>
                <InputLabel
                    htmlFor={`edit-project-description-${project.id}`}
                    value="Description"
                />
                <textarea
                    id={`edit-project-description-${project.id}`}
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    rows={3}
                    className="mt-1 block w-full rounded-studentlink border-gray-300 shadow-sm focus:border-primary-container focus:ring-primary-container"
                />
                <InputError message={errors.description} className="mt-2" />
            </div>
            <div>
                <InputLabel
                    htmlFor={`edit-project-deliverable-${project.id}`}
                    value="Type de livrable"
                />
                <DeliverableTypeSelect
                    id={`edit-project-deliverable-${project.id}`}
                    value={data.deliverable_type}
                    types={deliverableTypes}
                    onChange={(e) => setData('deliverable_type', e.target.value)}
                />
                <InputError message={errors.deliverable_type} className="mt-2" />
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel htmlFor={`edit-project-start-${project.id}`} value="Début" />
                    <TextInput
                        id={`edit-project-start-${project.id}`}
                        type="date"
                        value={data.starts_at}
                        onChange={(e) => setData('starts_at', e.target.value)}
                        className="mt-1 block w-full"
                        required
                    />
                    <InputError message={errors.starts_at} className="mt-2" />
                </div>
                <div>
                    <InputLabel htmlFor={`edit-project-end-${project.id}`} value="Fin" />
                    <TextInput
                        id={`edit-project-end-${project.id}`}
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
                <PrimaryButton disabled={processing}>Enregistrer</PrimaryButton>
                <button
                    type="button"
                    onClick={() => setOpen(false)}
                    className="text-sm text-on-surface/60"
                >
                    Annuler
                </button>
            </div>
        </form>
    );
}

function DeleteProjectControl({ project }) {
    const { delete: destroy, processing, errors, transform } = useForm({
        purge: false,
    });
    const [open, setOpen] = useState(false);

    const remove = () => {
        transform(() => ({ purge: !project.is_empty }));
        destroy(route('professor.projects.destroy', project.id), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    };

    return (
        <>
            <IconAction
                label="Effacer"
                icon="delete"
                tone="danger"
                onClick={() => setOpen(true)}
                disabled={processing}
            />
            <Modal show={open} maxWidth="md" onClose={() => setOpen(false)}>
                <div className="p-6">
                    <h2 className="text-lg font-medium text-on-surface">
                        Êtes-vous sûr ?
                    </h2>
                    <p className="mt-2 text-sm text-on-surface/70">
                        {project.is_empty
                            ? 'Ce projet sera effacé.'
                            : 'Ce projet, ses groupes et ses livrables seront effacés. Les étudiants restent inscrits au cours et dans les autres projets.'}
                    </p>
                    <InputError message={errors.purge} className="mt-2" />
                    <div className="mt-6 flex justify-end gap-3">
                        <button
                            type="button"
                            onClick={() => setOpen(false)}
                            className="text-sm text-on-surface/60"
                        >
                            Annuler
                        </button>
                        <button
                            type="button"
                            onClick={remove}
                            disabled={processing}
                            className="rounded-studentlink bg-red-700 px-3 py-2 text-sm font-medium text-white"
                        >
                            {project.is_empty ? 'Effacer' : 'Tout effacer'}
                        </button>
                    </div>
                </div>
            </Modal>
        </>
    );
}

function projectPeriod(project) {
    if (project.starts_at && project.ends_at) {
        return `Du ${project.starts_at} au ${project.ends_at}`;
    }

    return `Échéance ${project.ends_at ?? '—'}`;
}

export default function Dashboard({ courses, deliverableTypes }) {
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
                            <div className="mt-2 flex flex-wrap items-center gap-4">
                                <EditCourseForm course={course} />
                                <DeleteCourseControl course={course} />
                            </div>
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
                        deliverableTypes={deliverableTypes}
                        initiallyOpen={course.projects.length === 0}
                    />

                    {course.projects.map((project) => (
                        <div
                            key={project.id}
                            className="mb-4 overflow-hidden rounded-studentlink border border-primary-container/20 bg-card"
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
                                        {projectPeriod(project)} · Livrable :{' '}
                                        {project.deliverable_label} · {project.groups_count}{' '}
                                        groupes
                                    </p>
                                    <div className="mt-2 flex flex-wrap items-center gap-4">
                                        <EditProjectForm
                                            project={project}
                                            deliverableTypes={deliverableTypes}
                                        />
                                        <DeleteProjectControl project={project} />
                                    </div>
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
                                    className="inline-flex items-center gap-1 rounded-studentlink border border-primary-container/30 bg-card px-3 py-2 text-sm font-medium text-primary-container"
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
