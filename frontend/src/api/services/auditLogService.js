import apiClient from '../axios';

export const auditLogService = {
    async getAll(params = {}) {
        const { data } = await apiClient.get('/api/audit-logs', { params });
        return data;
    },
};
