import { useMemo } from 'react';
import { createApiClient } from '../api/client';
import useAuthStore from '../store/authStore';

const useApi = () => {
    const token = useAuthStore((state) => state.token);

    const client = useMemo(() => createApiClient({ token }), [token]);

    return client;
};

export default useApi;
