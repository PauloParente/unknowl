import { InertiaLinkProps } from '@inertiajs/vue3';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function urlIsActive(urlToCheck: NonNullable<InertiaLinkProps['href']>, currentUrl: string) {
    return toUrl(urlToCheck) === currentUrl;
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}

export function timeAgo(from: string | number | Date): string {
    const now = new Date().getTime();
    const then = new Date(from).getTime();
    const diff = Math.max(0, now - then);

    const seconds = Math.floor(diff / 1000);
    if (seconds < 45) return 'agora';
    if (seconds < 90) return 'há 1 min';

    const minutes = Math.floor(seconds / 60);
    if (minutes < 45) return `há ${minutes} min`;
    if (minutes < 90) return 'há 1 hora';

    const hours = Math.floor(minutes / 60);
    if (hours < 22) return `há ${hours} horas`;
    if (hours < 36) return 'há 1 dia';

    const days = Math.floor(hours / 24);
    if (days < 26) return `há ${days} dias`;
    if (days < 45) return 'há 1 mês';

    const months = Math.floor(days / 30);
    if (months < 11) return `há ${months} meses`;
    if (months < 18) return 'há 1 ano';

    const years = Math.floor(days / 365);
    return `há ${years} anos`;
}
