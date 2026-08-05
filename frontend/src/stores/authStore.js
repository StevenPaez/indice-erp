import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { authService } from '@/api/services/authService';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const loading = ref(false);

    const isAuthenticated = computed(() => !!user.value);

    async function fetchUser() {
        loading.value = true;
        try {
            user.value = await authService.getUser();
        } catch {
            user.value = null;
        } finally {
            loading.value = false;
        }
    }

    async function login(credentials) {
        const data = await authService.login(credentials);
        user.value = data;
        return data;
    }

    async function register(payload) {
        const data = await authService.register(payload);
        return data;
    }

    async function logout() {
        await authService.logout();
        user.value = null;
    }

    function clearUser() {
        user.value = null;
    }

    return {
        user,
        loading,
        isAuthenticated,
        fetchUser,
        login,
        register,
        logout,
        clearUser,
    };
});
