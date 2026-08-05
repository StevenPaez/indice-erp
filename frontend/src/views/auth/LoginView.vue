<template>
  <div class="login-page">
    <Card class="login-card">
      <template #title>
        <h2>Indice ERP</h2>
      </template>
      <template #subtitle>Iniciar sesion</template>
      <template #content>
        <div class="flex flex-col gap-4">
          <FloatLabel>
            <InputText id="email" v-model="email" type="email" class="w-full" />
            <label for="email">Email</label>
          </FloatLabel>
          <FloatLabel>
            <InputText id="password" v-model="password" type="password" class="w-full" />
            <label for="password">Contrasena</label>
          </FloatLabel>
          <Button label="Entrar" icon="pi pi-sign-in" @click="handleLogin" :loading="loading" class="w-full" />
          <div v-if="error" class="text-red-500 text-sm text-center">{{ error }}</div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';

const router = useRouter();
const authStore = useAuthStore();

const email = ref('');
const password = ref('');
const loading = ref(false);
const error = ref(null);

async function handleLogin() {
  loading.value = true;
  error.value = null;
  try {
    await authStore.login({ email: email.value, password: password.value });
    router.push('/books');
  } catch (e) {
    error.value = e.response?.data?.message || 'Error al iniciar sesion';
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--p-surface-100);
}
.login-card {
  width: 100%;
  max-width: 400px;
}
</style>
