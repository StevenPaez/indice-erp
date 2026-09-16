import axios from 'axios';

const apiClient = axios.create({

    baseURL: import.meta.env.VITE_API_URL || undefined,

    withCredentials: true,

    withXSRFToken: true,

    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    },

});

// CSRF cookie before mutating requests
apiClient.interceptors.request.use(async (config) => {

    if (['post', 'put', 'patch', 'delete'].includes(config.method?.toLowerCase())) {

        await apiClient.get('/sanctum/csrf-cookie');

    }

    return config;

});

// Handle auth errors
apiClient.interceptors.response.use(

    (response) => response,

    (error) => {

        if (error.response?.status === 401) {

            // Import lazily to avoid a circular dependency between the client
            // and the Pinia store during application bootstrap.
            import('@/stores/authStore').then(({ useAuthStore }) => {
                useAuthStore().clearUser();
            });

        }

        return Promise.reject(error);

    }

);

export default apiClient;
