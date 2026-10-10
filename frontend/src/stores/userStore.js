import { defineStore } from 'pinia';
import { ref } from 'vue';
import { userService } from '@/api/services/userService';

export const useUserStore = defineStore('users', () => {
    const users = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const pagination = ref({
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
    });

    async function fetchUsers(params = {}) {
        loading.value = true;
        error.value = null;

        try {
            const response = await userService.getAll(params);
            users.value = response.data;
            pagination.value = response.meta;
        } catch (requestError) {
            error.value = requestError.response?.data?.message || 'No fue posible cargar los usuarios.';
            throw requestError;
        } finally {
            loading.value = false;
        }
    }

    async function createUser(payload) {
        return userService.create(payload);
    }

    async function updateUser(id, payload) {
        const updated = await userService.update(id, payload);
        replaceUser(updated);
        return updated;
    }

    async function changeRole(id, role) {
        const updated = await userService.changeRole(id, role);
        replaceUser(updated);
        return updated;
    }

    async function changeStatus(id, isActive) {
        const updated = await userService.changeStatus(id, isActive);
        replaceUser(updated);
        return updated;
    }

    function replaceUser(updated) {
        users.value = users.value.map((user) => user.id === updated.id ? updated : user);
    }

    return {
        users,
        loading,
        error,
        pagination,
        fetchUsers,
        createUser,
        updateUser,
        changeRole,
        changeStatus,
    };
});
