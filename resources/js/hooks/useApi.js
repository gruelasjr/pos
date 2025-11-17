import { useCallback } from 'react';
import axios from 'axios';

const useApi = () => {
    const request = useCallback(async (config) => {
        const response = await axios.request(config);
        return response.data;
    }, []);

    return {
        get: (url, params = {}) => request({ method: 'get', url, params }),
        post: (url, data = {}) => request({ method: 'post', url, data }),
        patch: (url, data = {}) => request({ method: 'patch', url, data }),
        del: (url) => request({ method: 'delete', url }),
    };
};

export default useApi;
