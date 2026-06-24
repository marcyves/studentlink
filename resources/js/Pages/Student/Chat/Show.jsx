import Icon from '@/Components/Icon';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import StudentLayout from '@/Layouts/StudentLayout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

function MessageBubble({ message, isOwn }) {
    const time = new Date(message.created_at).toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit',
    });

    return (
        <div className={`flex ${isOwn ? 'justify-end' : 'justify-start'}`}>
            <div
                className={`max-w-[85%] rounded-studentlink px-3 py-2 ${
                    isOwn
                        ? 'bg-primary-container text-white'
                        : 'border border-primary-container/15 bg-white text-on-surface'
                }`}
            >
                {!isOwn && (
                    <p className="mb-1 text-xs font-semibold text-secondary">
                        {message.user.name}
                    </p>
                )}
                <p className="whitespace-pre-wrap text-sm">{message.body}</p>
                <p
                    className={`mt-1 text-[10px] ${
                        isOwn ? 'text-white/70' : 'text-on-surface/40'
                    }`}
                >
                    {time}
                </p>
            </div>
        </div>
    );
}

export default function Show({ group, messages: initialMessages, members }) {
    const { auth } = usePage().props;
    const reverbEnabled = Boolean(import.meta.env.VITE_REVERB_APP_KEY);
    const [messages, setMessages] = useState(initialMessages);
    const bottomRef = useRef(null);
    const { data, setData, post, processing, reset } = useForm({ body: '' });

    useEffect(() => {
        setMessages(initialMessages);
    }, [initialMessages]);

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    useEffect(() => {
        if (!window.Echo) {
            return undefined;
        }

        const channel = window.Echo.private(`group.${group.id}`);

        channel.listen('.message.sent', (payload) => {
            setMessages((current) => {
                if (current.some((m) => m.id === payload.message.id)) {
                    return current;
                }

                return [...current, payload.message];
            });
        });

        return () => {
            channel.stopListening('.message.sent');
            window.Echo.leave(`group.${group.id}`);
        };
    }, [group.id]);

    const submit = (e) => {
        e.preventDefault();
        post(route('student.chat.store', group.id), {
            preserveScroll: true,
            onSuccess: () => {
                reset('body');
                router.reload({ only: ['messages'] });
            },
        });
    };

    return (
        <StudentLayout title={group.name}>
            <Head title={`Chat — ${group.name}`} />

            <Link
                href={route('student.chat.index')}
                className="mb-3 inline-flex items-center gap-1 text-sm text-primary-container"
            >
                <Icon name="arrow_back" className="text-base" />
                Groupes
            </Link>

            <p className="mb-4 text-xs text-on-surface/50">
                {group.project} · {members.length} en ligne (membres :{' '}
                {members.join(', ')})
            </p>

            <div className="mb-4 flex max-h-[55vh] min-h-[280px] flex-col gap-3 overflow-y-auto rounded-studentlink border border-primary-container/15 bg-surface-container/30 p-3">
                {messages.length === 0 ? (
                    <p className="m-auto text-sm text-on-surface/50">
                        Aucun message — lancez la conversation.
                    </p>
                ) : (
                    messages.map((message) => (
                        <MessageBubble
                            key={message.id}
                            message={message}
                            isOwn={message.user.id === auth.user.id}
                        />
                    ))
                )}
                <div ref={bottomRef} />
            </div>

            <form onSubmit={submit} className="flex gap-2">
                <TextInput
                    value={data.body}
                    onChange={(e) => setData('body', e.target.value)}
                    placeholder="Votre message…"
                    className="flex-1"
                    required
                />
                <PrimaryButton disabled={processing} aria-label="Envoyer">
                    <Icon name="send" />
                </PrimaryButton>
            </form>

            {!reverbEnabled && (
                <p className="mt-2 text-xs text-on-surface/40">
                    Temps réel désactivé — lancez Reverb pour le chat live.
                </p>
            )}
        </StudentLayout>
    );
}
