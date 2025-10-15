import { useAvatar } from './useAvatar';

export function getInitials(fullName?: string): string {
    if (!fullName) return '';

    const names = fullName.trim().split(' ');

    if (names.length === 0) return '';
    if (names.length === 1) return names[0].charAt(0).toUpperCase();

    return `${names[0].charAt(0)}${names[names.length - 1].charAt(0)}`.toUpperCase();
}

export function getCommunityInitials(communityName?: string): string {
    if (!communityName) return '';

    // Remove o prefixo r/ se existir
    const cleanName = communityName.startsWith('r/') ? communityName.substring(2) : communityName;
    
    // Se o nome limpo ainda contém espaços, pega a primeira letra de cada palavra
    const names = cleanName.trim().split(' ');
    
    if (names.length === 0) return '';
    if (names.length === 1) return names[0].charAt(0).toUpperCase();
    
    return `${names[0].charAt(0)}${names[names.length - 1].charAt(0)}`.toUpperCase();
}

export function useInitials() {
    const { getUserAvatarFallback, getCommunityAvatarFallback } = useAvatar();
    
    return { 
        getInitials: getUserAvatarFallback, 
        getCommunityInitials: getCommunityAvatarFallback 
    };
}
