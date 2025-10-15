import { computed } from 'vue';

/**
 * Composable para gerenciar avatares de forma consistente
 */
export function useAvatar() {
    /**
     * Gera URL do avatar com fallback para iniciais
     */
    const getAvatarUrl = (avatar?: string | null): string | null => {
        if (!avatar) return null;
        return `/storage/${avatar}`;
    };

    /**
     * Verifica se deve mostrar a imagem do avatar
     */
    const shouldShowAvatar = (avatar?: string | null): boolean => {
        return Boolean(avatar && avatar.trim() !== '');
    };

    /**
     * Gera fallback para avatar de usuário (iniciais)
     */
    const getUserAvatarFallback = (name?: string): string => {
        if (!name) return '';
        
        const names = name.trim().split(' ');
        if (names.length === 0) return '';
        if (names.length === 1) return names[0].charAt(0).toUpperCase();
        
        return `${names[0].charAt(0)}${names[names.length - 1].charAt(0)}`.toUpperCase();
    };

    /**
     * Gera fallback para avatar de comunidade (iniciais)
     */
    const getCommunityAvatarFallback = (name?: string): string => {
        if (!name) return '';
        
        // Remove o prefixo r/ se existir
        const cleanName = name.startsWith('r/') ? name.substring(2) : name;
        const names = cleanName.trim().split(' ');
        
        if (names.length === 0) return '';
        if (names.length === 1) return names[0].charAt(0).toUpperCase();
        
        return `${names[0].charAt(0)}${names[names.length - 1].charAt(0)}`.toUpperCase();
    };

    /**
     * Gera URL da capa com fallback
     */
    const getCoverUrl = (coverUrl?: string | null): string => {
        if (coverUrl) return `/storage/${coverUrl}`;
        return '/images/placeholders/community-cover.svg';
    };

    return {
        getAvatarUrl,
        shouldShowAvatar,
        getUserAvatarFallback,
        getCommunityAvatarFallback,
        getCoverUrl,
    };
}
