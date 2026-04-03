export type User = {
    id: number;
    name: string;
    email: string;
    roles: string[];
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
    isAdmin: boolean;
    canViewVoters: boolean;
    canViewCandidates: boolean;
    canCallCenter: boolean;
    canResults: boolean;
    canZeroday: boolean;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
