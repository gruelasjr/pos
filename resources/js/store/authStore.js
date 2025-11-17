import { create } from 'zustand';

const persistedSession = () => {
    if (typeof window === 'undefined') {
        return { token: null, user: null };
    }

    return {
        token: localStorage.getItem('pos-token'),
        user: JSON.parse(localStorage.getItem('pos-user') || 'null'),
    };
};

const syncStorage = (token, user) => {
    if (typeof window === 'undefined') {
        return;
    }

    if (token) {
        localStorage.setItem('pos-token', token);
    } else {
        localStorage.removeItem('pos-token');
    }

    if (user) {
        localStorage.setItem('pos-user', JSON.stringify(user));
    } else {
        localStorage.removeItem('pos-user');
    }
};

const useAuthStore = create((set) => ({
    ...persistedSession(),
    setSession: (payload) =>
        set(() => {
            syncStorage(payload.token, payload.user);
            return payload;
        }),
    logout: () =>
        set(() => {
            syncStorage(null, null);
            return { token: null, user: null };
        }),
}));

export default useAuthStore;
