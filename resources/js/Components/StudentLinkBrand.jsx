import Icon from '@/Components/Icon';

export default function StudentLinkBrand({
    className = '',
    iconClassName = 'text-[40px] md:text-[48px]',
    titleClassName = 'text-3xl font-bold tracking-tight text-primary md:text-5xl',
    showTagline = true,
}) {
    return (
        <div className={`text-center ${className}`}>
            <div className="mb-2 flex items-center justify-center">
                <Icon
                    name="school"
                    filled
                    className={`text-primary ${iconClassName}`}
                />
            </div>
            <h1 className={titleClassName}>StudentLink</h1>
            {showTagline && (
                <p className="mt-2 text-base text-on-surface/60 md:text-lg">
                    L&apos;excellence académique par la collaboration.
                </p>
            )}
        </div>
    );
}
