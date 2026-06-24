export default function ScoreSlider({ id, label, hint, max, value, onChange, disabled = false }) {
    return (
        <div className="space-y-2">
            <div className="flex items-end justify-between gap-4">
                <div>
                    <label htmlFor={id} className="block text-sm font-medium text-on-surface">
                        {label}
                    </label>
                    {hint && (
                        <p className="text-xs text-on-surface/60">{hint}</p>
                    )}
                </div>
                <span className="text-lg font-bold text-secondary">{value}/{max}</span>
            </div>
            <input
                id={id}
                type="range"
                min={1}
                max={max}
                value={value}
                disabled={disabled}
                onChange={(e) => onChange(Number(e.target.value))}
                className="slider-peer h-2 w-full cursor-pointer appearance-none rounded-full bg-secondary/20 accent-secondary"
            />
        </div>
    );
}
