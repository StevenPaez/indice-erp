import axios from 'axios';

const apiClient = axios.create({
    baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000',
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
        await axios.get('/sanctum/csrf-cookie', {
            baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000',
            withCredentials: true,
        });
    }
    return config;
});

// Handle auth errors
apiClient.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            const authStore = useAuthStore();
            authStore.clearUser();
        }
        return Promise.reject(error);
    }
);

// Lazy import to avoid circular dependency
import { useAuthStore } from '@/stores/authStore';

export default apiClient;
