import { usePage } from '@inertiajs/react';

export default function FlashMessage() {
    const { flash } = usePage().props;

    if (!flash?.success) {
        return null;
    }

    return (
        <div className="mb-4 rounded-studentlink border border-tertiary-container/30 bg-tertiary-container/10 px-4 py-3 text-sm text-on-surface">
            {flash.success}
        </div>
    );
}
