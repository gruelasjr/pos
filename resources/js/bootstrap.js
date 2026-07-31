import axios from 'axios';

window.axios = axios;
axios.defaults.baseURL = '/api/v1';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response && error.response.status === 401) {
            window.location.assign('/login');
        }
        return Promise.reject(error);
    },
);
