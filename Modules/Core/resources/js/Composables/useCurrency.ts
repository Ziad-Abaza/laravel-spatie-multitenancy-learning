import { usePage } from '@inertiajs/vue3';

/**
 * Currency formatting backed by the platform billing setting
 * (shared Inertia prop `billing.currency`). Per-row currencies
 * (e.g. subscription snapshots) may still be passed explicitly.
 */
export function useCurrency() {
    const page = usePage();

    const currency = (): string => (page.props.billing as any)?.currency ?? 'USD';
    const locale = (): string => ((page.props.locale as any)?.current === 'ar' ? 'ar-EG' : 'en-US');

    const format = (amount: number | string, override?: string): string =>
        new Intl.NumberFormat(locale(), {
            style: 'currency',
            currency: override ?? currency(),
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        }).format(Number(amount) || 0);

    return { currency, format };
}
