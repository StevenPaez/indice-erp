import apiClient from '../axios';

export const userService = {
    async getAll(params = {}) {
        const { data } = await apiClient.get('/api/users', { params });
        return data;
    },

    async create(payload) {
        const { data } = await apiClient.post('/api/users', payload);
        return data.data;
    },

    async update(id, payload) {
        const { data } = await apiClient.put(`/api/users/${id}`, payload);
        return data.data;
    },

    async changeRole(id, role) {
        const { data } = await apiClient.patch(`/api/users/${id}/role`, { role });
        return data.data;
    },

    async changeStatus(id, isActive) {
        const { data } = await apiClient.patch(`/api/users/${id}/status`, {
            is_active: isActive,
        });
        return data.data;
    },
};
