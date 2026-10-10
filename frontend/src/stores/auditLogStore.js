import { defineStore } from 'pinia';
import { ref } from 'vue';
import { auditLogService } from '@/api/services/auditLogService';

export const useAuditLogStore = defineStore('auditLogs', () => {
    const auditLogs = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const pagination = ref({
        current_page: 1,
        last_page: 1,
        per_page: 25,
        total: 0,
    });

    async function fetchAuditLogs(params = {}) {
        loading.value = true;
        error.value = null;

        try {
            const response = await auditLogService.getAll(params);
            auditLogs.value = response.data;
            pagination.value = response.meta;
        } catch (requestError) {
            error.value = requestError.response?.data?.message || 'No fue posible cargar la auditoría.';
            throw requestError;
        } finally {
            loading.value = false;
        }
    }

    return {
        auditLogs,
        loading,
        error,
        pagination,
        fetchAuditLogs,
    };
});
