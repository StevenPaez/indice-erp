<template>
  <div class="auth-page">
    <Card class="auth-card">
      <template #title>
        <div class="text-center mb-2">
          <h2 class="text-2xl font-semibold text-surface-900">Indice ERP</h2>
        </div>
      </template>
      <template #subtitle>
        <div class="text-center text-surface-500 mb-6">Iniciar sesión</div>
      </template>
      <template #content>
        <div class="flex flex-col gap-5">
          <div class="flex flex-col gap-2">
            <FloatLabel>
              <InputText
                id="email"
                v-model="email"
                type="email"
                class="w-full"
                :class="{ 'p-invalid': fieldErrors.email }"
              />
              <label for="email">Correo electrónico</label>
            </FloatLabel>
            <small v-if="fieldErrors.email" class="text-red-500">{{ fieldErrors.email }}</small>
          </div>

          <div class="flex flex-col gap-2">
            <FloatLabel>
              <InputText
                id="password"
                v-model="password"
                type="password"
                class="w-full"
                :class="{ 'p-invalid': fieldErrors.password }"
              />
              <label for="password">Contraseña</label>
            </FloatLabel>
            <small v-if="fieldErrors.password" class="text-red-500">{{ fieldErrors.password }}</small>
          </div>

          <Button
            label="Entrar"
            icon="pi pi-sign-in"
            @click="handleLogin"
            :loading="loading"
            class="w-full mt-2"
          />

          <div v-if="generalError" class="text-red-500 text-sm text-center">{{ generalError }}</div>

          <div class="text-center text-sm text-surface-500">
            ¿No tienes cuenta?
            <router-link to="/register" class="text-primary hover:underline">Regístrate</router-link>
          </div>
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
const generalError = ref(null);
const fieldErrors = ref({});

async function handleLogin() {
  loading.value = true;
  generalError.value = null;
  fieldErrors.value = {};

  try {
    await authStore.login({ email: email.value, password: password.value });
    router.push('/books');
  } catch (e) {
    if (e.response?.data?.errors) {
      const errors = e.response.data.errors;
      fieldErrors.value = Object.fromEntries(
        Object.entries(errors).map(([key, msgs]) => [key, Array.isArray(msgs) ? msgs[0] : msgs])
      );
    }
    generalError.value = e.response?.data?.message || 'Error al iniciar sesión';
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--p-surface-100);
  padding: 1rem;
}
.auth-card {
  width: 100%;
  max-width: 420px;
  padding: 1.5rem;
}
.auth-card :deep(.p-card-content) {
  padding-top: 0.5rem;
}
.text-primary {
  color: var(--p-primary-color);
  text-decoration: none;
  font-weight: 500;
}
</style>
