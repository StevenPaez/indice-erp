import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { authService } from '@/api/services/authService';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const loading = ref(false);
    const initialized = ref(false);

    const isAuthenticated = computed(() => !!user.value);

    async function fetchUser() {
        if (initialized.value) {
            return user.value;
        }

        loading.value = true;
        try {
            user.value = await authService.getUser();
        } catch {
            user.value = null;
        } finally {
            initialized.value = true;
            loading.value = false;
        }

        return user.value;
    }

    async function login(credentials) {
        const data = await authService.login(credentials);
        user.value = data;
        initialized.value = true;
        return data;
    }

    async function register(payload) {
        const data = await authService.register(payload);
        return data;
    }

    async function logout() {
        await authService.logout();
        user.value = null;
        initialized.value = true;
    }

    function clearUser() {
        user.value = null;
        initialized.value = true;
    }

    return {
        user,
        loading,
        isAuthenticated,
        initialized,
        fetchUser,
        login,
        register,
        logout,
        clearUser,
    };
});
