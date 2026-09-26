import Icon from '@/Components/Icon';
import { useT } from '@/i18n';

export default function DeliverablePreview({ deliverable }) {
    const t = useT();
    if (!deliverable) {
        return null;
    }

    if (deliverable.type === 'none') {
        return (
            <p className="text-sm text-on-surface/70">
                {t("Aucun livrable n'a été demandé.")}
            </p>
        );
    }

    if (!deliverable.submitted || deliverable.message) {
        return (
            <p className="text-sm text-on-surface/70">
                {deliverable.message ??
                    t("Ce groupe n'a pas encore rendu son livrable.")}
            </p>
        );
    }

    if (deliverable.type === 'image' && deliverable.url) {
        return (
            <img
                src={deliverable.url}
                alt={deliverable.original_name || t('Livrable du groupe')}
                className="max-h-80 w-full rounded-studentlink object-contain"
            />
        );
    }

    if (deliverable.type === 'video' && deliverable.url) {
        return (
            <video controls src={deliverable.url} className="w-full rounded-studentlink">
                {t('Votre navigateur ne lit pas cette vidéo.')}
            </video>
        );
    }

    if (deliverable.type === 'youtube' && deliverable.embed_url) {
        return (
            <iframe
                src={deliverable.embed_url}
                title={t('Vidéo YouTube')}
                className="aspect-video w-full rounded-studentlink"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowFullScreen
            />
        );
    }

    if (deliverable.type === 'link' && deliverable.link) {
        return (
            <a
                href={deliverable.link}
                target="_blank"
                rel="noopener noreferrer"
                className="break-all text-sm font-medium text-primary-container underline"
            >
                {deliverable.link}
            </a>
        );
    }

    if (deliverable.type === 'file' && deliverable.url) {
        return (
            <a
                href={deliverable.url}
                download={deliverable.original_name || true}
                className="inline-flex items-center gap-1 text-sm font-medium text-primary-container"
            >
                <Icon name="download" className="text-base" />
                {t('Télécharger :name', {
                    name: deliverable.original_name || t('le fichier'),
                })}
            </a>
        );
    }

    return null;
}
