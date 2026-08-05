<template>
  <div>
    <Toast />
    <ConfirmDialog />
    <router-view />
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useAuthStore } from '@/stores/authStore';
import { useRouter, useRoute } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();

onMounted(async () => {
  await authStore.fetchUser();
  if (!authStore.isAuthenticated && route.path !== '/login') {
    router.push('/login');
  }
});
</script>

<style>
body {
  margin: 0;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background: var(--p-surface-50);
}
</style>
