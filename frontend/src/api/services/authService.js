import apiClient from '../axios';

export const authService = {
    async register({ name, email, password, password_confirmation }) {
        const { data } = await apiClient.post('/api/register', {
            name, email, password, password_confirmation,
        });
        return data;
    },

    async login({ email, password }) {
        const { data } = await apiClient.post('/api/login', { email, password });
        return data;
    },

    async logout() {
        await apiClient.post('/api/logout');
    },

    async getUser() {
        const { data } = await apiClient.get('/api/user');
        return data;
    },
};
