import apiClient from '../axios';

export const authService = {
    async login({ email, password }) {
        const { data } = await apiClient.post('/api/login', { email, password });
        return data;
    },

    async forgotPassword(email) {
        const { data } = await apiClient.post('/api/forgot-password', { email });
        return data;
    },

    async resetPassword(payload) {
        const { data } = await apiClient.post('/api/reset-password', payload);
        return data;
    },

    async logout() {
        await apiClient.post('/api/logout');
    },

    async getUser() {
        const { data } = await apiClient.get('/api/user');
        return data;
    },

    async changePassword(payload) {
        const { data } = await apiClient.put('/api/user/password', payload);
        return data;
    },
};
