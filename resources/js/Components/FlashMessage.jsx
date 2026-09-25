import { usePage } from '@inertiajs/react';

export default function FlashMessage() {
    const { flash } = usePage().props;

    if (!flash?.success && !flash?.error) {
        return null;
    }

    return (
        <div className="mb-4 space-y-3">
            {flash.success && (
                <div className="rounded-studentlink border border-tertiary-container/30 bg-tertiary-container/10 px-4 py-3 text-sm text-on-surface">
                    {flash.success}
                </div>
            )}
            {flash.error && (
                <div className="rounded-studentlink border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {flash.error}
                </div>
            )}
        </div>
    );
}
