import FlashMessage from '@/Components/FlashMessage';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import ProfessorLayout from '@/Layouts/ProfessorLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Edit({ project, rubric }) {
    const { data, setData, put, processing, errors } = useForm({
        name: rubric.name,
        criteria: rubric.criteria.length
            ? rubric.criteria
            : [{ label: '', weight: 33, max_score: 5 }],
    });

    const updateCriterion = (index, field, value) => {
        const criteria = [...data.criteria];
        criteria[index] = { ...criteria[index], [field]: value };
        setData('criteria', criteria);
    };

    const addCriterion = () => {
        setData('criteria', [
            ...data.criteria,
            { label: '', weight: 10, max_score: 5 },
        ]);
    };

    const removeCriterion = (index) => {
        setData(
            'criteria',
            data.criteria.filter((_, i) => i !== index),
        );
    };

    const totalWeight = data.criteria.reduce(
        (sum, c) => sum + Number(c.weight || 0),
        0,
    );

    return (
        <ProfessorLayout title="Configuration des grilles">
            <Head title="Configuration des grilles" />
            <FlashMessage />

            <p className="mb-6 text-sm text-on-surface/60">
                {project.course} · {project.title}
            </p>

            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    put(route('professor.rubrics.update', project.id));
                }}
                className="max-w-2xl space-y-6"
            >
                <div>
                    <label className="text-sm font-medium text-on-surface">
                        Nom de la grille
                    </label>
                    <TextInput
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        className="mt-1 w-full"
                    />
                </div>

                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h2 className="font-medium text-on-surface">Critères</h2>
                        <p
                            className={`text-sm ${
                                totalWeight === 100
                                    ? 'text-tertiary'
                                    : 'text-red-600'
                            }`}
                        >
                            Poids total : {totalWeight} %
                        </p>
                    </div>

                    {data.criteria.map((criterion, index) => (
                        <div
                            key={index}
                            className="grid gap-3 rounded-studentlink border border-primary-container/20 bg-white p-4 sm:grid-cols-[1fr_100px_100px_auto]"
                        >
                            <TextInput
                                value={criterion.label}
                                onChange={(e) =>
                                    updateCriterion(index, 'label', e.target.value)
                                }
                                placeholder="Critère"
                            />
                            <TextInput
                                type="number"
                                value={criterion.weight}
                                onChange={(e) =>
                                    updateCriterion(index, 'weight', e.target.value)
                                }
                                placeholder="Poids %"
                            />
                            <TextInput
                                type="number"
                                value={criterion.max_score}
                                onChange={(e) =>
                                    updateCriterion(
                                        index,
                                        'max_score',
                                        e.target.value,
                                    )
                                }
                                placeholder="Max"
                            />
                            <button
                                type="button"
                                onClick={() => removeCriterion(index)}
                                className="text-sm text-red-600"
                            >
                                Suppr.
                            </button>
                        </div>
                    ))}

                    {errors.criteria && (
                        <p className="text-sm text-red-600">{errors.criteria}</p>
                    )}

                    <button
                        type="button"
                        onClick={addCriterion}
                        className="text-sm font-medium text-primary-container"
                    >
                        + Ajouter un critère
                    </button>
                </div>

                <div className="flex gap-3">
                    <PrimaryButton disabled={processing}>
                        Enregistrer
                    </PrimaryButton>
                    <Link
                        href={route('dashboard')}
                        className="inline-flex items-center rounded-studentlink border border-outline-variant/40 px-4 py-2 text-sm text-on-surface"
                    >
                        Annuler
                    </Link>
                </div>
            </form>
        </ProfessorLayout>
    );
}
