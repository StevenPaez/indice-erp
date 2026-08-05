import apiClient from '../axios';

export const bookService = {
    async getAll(params = {}) {
        const { data } = await apiClient.get('/api/books', { params });
        return data;
    },

    async getOne(id) {
        const { data } = await apiClient.get(`/api/books/${id}`);
        return data;
    },

    async create(payload) {
        const { data } = await apiClient.post('/api/books', payload);
        return data;
    },

    async update(id, payload) {
        const { data } = await apiClient.put(`/api/books/${id}`, payload);
        return data;
    },

    async delete(id) {
        await apiClient.delete(`/api/books/${id}`);
    },

    async getLowStock() {
        const { data } = await apiClient.get('/api/books/low-stock');
        return data;
    },
};
