import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
    communities?: Community[];
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    bio?: string;
    avatar?: string;
    cover_url?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface Community {
    id: number;
    name: string; // e.g., javascript
    title: string; // display name
    avatar?: string;
    cover_url?: string;
    description?: string;
    created_at: string;
    rules?: string[];
    members_count?: number;
    online_count?: number;
    posts_count?: number;
}

export interface AuthorSummary {
    id: number;
    name: string;
    avatar?: string;
}

export interface Post {
    id: number;
    community: Community;
    author: AuthorSummary;
    title: string;
    text?: string;
    imageUrl?: string;
    mediaUrls?: string[];
    created_at: string;
    score: number; // likes - dislikes
    comments_count: number;
    is_pinned?: boolean;
    pinned_at?: string;
    user_vote?: 'like' | 'dislike' | null;
}

export interface CommentEdit {
    id: number;
    text: string;
    version_number: number;
    created_at: string;
}

export interface Comment {
    id: number;
    author: AuthorSummary;
    text: string;
    original_text?: string;
    edit_history: CommentEdit[];
    created_at: string;
    updated_at: string;
    score: number;
    replies?: Comment[];
    user_vote?: 'like' | 'dislike' | null;
}

export type BreadcrumbItemType = BreadcrumbItem;
