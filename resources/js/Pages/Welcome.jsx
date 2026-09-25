import FlashMessage from '@/Components/FlashMessage';
import Icon from '@/Components/Icon';
import ProfessorAccessForm from '@/Components/ProfessorAccessForm';
import StudentLinkBrand from '@/Components/StudentLinkBrand';
import ThemeToggle from '@/Components/ThemeToggle';
import { Head, Link } from '@inertiajs/react';

const features = [
    {
        icon: 'groups',
        title: 'Groupes autonomes',
        text: 'Constituez votre équipe, rejoignez un cours et coordonnez-vous via le chat de groupe.',
    },
    {
        icon: 'rate_review',
        title: 'Évaluation par les pairs',
        text: 'Notation inter-groupe et intra-groupe, guidée par des grilles configurables.',
    },
    {
        icon: 'dashboard',
        title: 'Pilotage enseignant',
        text: 'Suivez les rendus, configurez les critères et exportez les notes consolidées.',
    },
];

export default function Welcome({ auth, canLogin, canRegister, registrationHint }) {
    return (
        <>
            <Head title="Accueil" />

            <div className="mesh-gradient flex min-h-screen flex-col">
                <header className="mx-auto flex w-full max-w-6xl items-center justify-between px-4 py-6 md:px-8">
                    <Link href="/" className="flex items-center gap-2">
                        <Icon
                            name="school"
                            filled
                            className="text-2xl text-primary"
                        />
                        <span className="text-lg font-bold text-primary">
                            StudentLink
                        </span>
                    </Link>

                    <nav className="flex items-center gap-2 sm:gap-3">
                        <ThemeToggle />
                        {auth.user ? (
                            <Link
                                href={route('dashboard')}
                                className="rounded-studentlink bg-primary-container px-4 py-2 text-sm font-medium text-white transition hover:bg-primary"
                            >
                                Mon espace
                            </Link>
                        ) : (
                            <>
                                {canLogin && (
                                    <Link
                                        href={route('login')}
                                        className="rounded-studentlink px-4 py-2 text-sm font-medium text-primary-container transition hover:bg-surface-container"
                                    >
                                        Connexion
                                    </Link>
                                )}
                                {canRegister && (
                                    <Link
                                        href={route('register')}
                                        className="rounded-studentlink bg-secondary px-4 py-2 text-sm font-medium text-white transition hover:bg-secondary-container"
                                    >
                                        Créer un compte
                                    </Link>
                                )}
                            </>
                        )}
                    </nav>
                </header>

                <main className="mx-auto flex w-full max-w-6xl flex-1 flex-col px-4 pb-16 pt-8 md:px-8 md:pt-16">
                    <FlashMessage />
                    <section className="mx-auto max-w-3xl text-center">
                        <StudentLinkBrand showTagline={false} />

                        <p className="mt-4 text-lg text-on-surface/70 md:text-xl">
                            Plateforme de collaboration académique — autonomie
                            étudiante, évaluation par les pairs, supervision
                            enseignante.
                        </p>

                        {!auth.user && (
                            <div className="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                                {canRegister && (
                                    <Link
                                        href={route('register')}
                                        className="inline-flex w-full items-center justify-center gap-2 rounded-studentlink bg-secondary px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-secondary-container sm:w-auto"
                                    >
                                        <Icon name="person_add" />
                                        Commencer gratuitement
                                    </Link>
                                )}
                                {canLogin && (
                                    <Link
                                        href={route('login')}
                                        className="inline-flex w-full items-center justify-center gap-2 rounded-studentlink border border-primary-container/30 bg-card px-6 py-3 text-sm font-semibold text-primary-container transition hover:border-secondary/40 hover:bg-surface-container/50 sm:w-auto"
                                    >
                                        <Icon name="login" />
                                        Se connecter
                                    </Link>
                                )}
                            </div>
                        )}
                    </section>

                    <section className="mt-16 grid gap-4 md:mt-24 md:grid-cols-3 md:gap-6">
                        {features.map((feature) => (
                            <article
                                key={feature.title}
                                className="rounded-studentlink border border-primary-container/10 bg-card/80 p-6 shadow-sm backdrop-blur-sm transition hover:border-secondary/30 hover:shadow-md"
                            >
                                <div className="mb-4 flex h-11 w-11 items-center justify-center rounded-studentlink bg-secondary/10 text-secondary">
                                    <Icon name={feature.icon} />
                                </div>
                                <h2 className="text-lg font-semibold text-on-surface">
                                    {feature.title}
                                </h2>
                                <p className="mt-2 text-sm leading-relaxed text-on-surface/60">
                                    {feature.text}
                                </p>
                            </article>
                        ))}
                    </section>

                    {!auth.user && canLogin && canRegister && (
                        <section className="mt-16 rounded-studentlink border border-primary-container/15 bg-surface-container/40 p-8 text-center md:mt-24">
                            <h2 className="text-xl font-semibold text-on-surface">
                                Prêt à rejoindre votre promotion ?
                            </h2>
                            <p className="mx-auto mt-2 max-w-lg text-sm text-on-surface/60">
                                Inscrivez-vous avec votre adresse
                                institutionnelle
                                {registrationHint && (
                                    <> ({registrationHint})</>
                                )}
                                , puis rejoignez un cours avec le code fourni par
                                votre enseignant.
                            </p>
                            <div className="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row">
                                <Link
                                    href={route('register')}
                                    className="inline-flex items-center gap-2 rounded-studentlink bg-primary-container px-5 py-2.5 text-sm font-medium text-white transition hover:bg-primary"
                                >
                                    Créer un compte
                                </Link>
                                <Link
                                    href={route('login')}
                                    className="text-sm font-medium text-primary-container underline-offset-2 hover:underline"
                                >
                                    J&apos;ai déjà un compte
                                </Link>
                            </div>
                        </section>
                    )}

                    {!auth.user && (
                        <section
                            id="professor-access"
                            className="mt-16 rounded-studentlink border border-secondary/20 bg-card/90 p-8 md:mt-24"
                        >
                            <div className="mx-auto max-w-md">
                                <h2 className="text-center text-xl font-semibold text-on-surface">
                                    Vous êtes professeur ?
                                </h2>
                                <p className="mt-2 text-center text-sm text-on-surface/60">
                                    Les comptes enseignants sont créés par un
                                    administrateur. Demandez un accès ci-dessous.
                                </p>
                                <div className="mt-6">
                                    <ProfessorAccessForm />
                                </div>
                            </div>
                        </section>
                    )}
                </main>

                <footer className="border-t border-primary-container/10 py-6 text-center text-xs text-on-surface/40">
                    StudentLink — Academic Autonomy
                </footer>
            </div>
        </>
    );
}
