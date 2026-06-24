import StudentLinkBrand from '@/Components/StudentLinkBrand';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="mesh-gradient flex min-h-screen flex-col items-center justify-center px-4 py-10">
            <Link href="/" className="mb-8">
                <StudentLinkBrand titleClassName="text-3xl font-bold tracking-tight text-primary" />
            </Link>

            <div className="w-full max-w-md rounded-studentlink border border-primary-container/10 bg-white p-6 shadow-sm md:p-8">
                {children}
            </div>
        </div>
    );
}
