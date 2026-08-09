<template>
  <div class="auth-page">
    <div class="auth-card">
      <div class="auth-header">
        <h1 class="auth-title">Iniciar sesión</h1>
        <p class="auth-subtitle">Ingresa tus datos para acceder a tu cuenta</p>
      </div>

      <form class="auth-form" @submit.prevent="handleLogin">
        <div class="field-group">
          <label for="login-email" class="field-label">Correo</label>
          <InputText
            id="login-email"
            v-model="email"
            type="email"
            autocomplete="email"
            placeholder="tucorreo@ejemplo.com"
            class="w-full"
            :class="{ 'p-invalid': fieldErrors.email }"
          />
          <small v-if="fieldErrors.email" class="field-error">{{ fieldErrors.email }}</small>
        </div>

        <div class="field-group">
          <label for="login-password" class="field-label">Contraseña</label>
          <div class="password-wrapper">
            <InputText
              id="login-password"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              autocomplete="current-password"
              placeholder="Tu contraseña"
              class="w-full password-input"
              :class="{ 'p-invalid': fieldErrors.password }"
            />
            <button
              type="button"
              class="password-toggle"
              :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
              @click="showPassword = !showPassword"
            >
              <i :class="showPassword ? 'pi pi-eye-slash' : 'pi pi-eye'"></i>
            </button>
          </div>
          <small v-if="fieldErrors.password" class="field-error">{{ fieldErrors.password }}</small>
        </div>

        <Button
          type="submit"
          label="Entrar"
          :loading="loading"
          class="w-full submit-btn"
        />

        <div v-if="generalError" class="general-error">{{ generalError }}</div>
      </form>

      <p class="auth-footer">
        ¿No tienes cuenta?
        <router-link to="/register" class="auth-link">Regístrate</router-link>
      </p>
    </div>
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
const showPassword = ref(false);
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
  min-height: 100dvh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--p-surface-100);
  padding: 1rem;
  margin: 0;
  box-sizing: border-box;
}

.auth-card {
  width: 100%;
  max-width: 380px;
  margin: 0 auto;
  background: var(--p-surface-0, #fff);
  border: 1px solid var(--p-surface-200, #e5e7eb);
  border-radius: 1rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
  padding: 2rem;
}

.auth-header {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
  text-align: center;
  margin-bottom: 1.5rem;
}

.auth-title {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
  line-height: 1.3;
  color: var(--p-surface-900);
  letter-spacing: -0.01em;
}

.auth-subtitle {
  margin: 0;
  font-size: 0.875rem;
  color: var(--p-surface-500);
  line-height: 1.5;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.field-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.field-label {
  font-size: 0.875rem;
  font-weight: 500;
  line-height: 1;
  color: var(--p-surface-700);
  user-select: none;
}

.password-wrapper {
  position: relative;
}

.password-input {
  padding-right: 2.5rem;
}

.password-toggle {
  position: absolute;
  right: 0.5rem;
  top: 50%;
  transform: translateY(-50%);
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border: none;
  border-radius: 0.375rem;
  background: transparent;
  color: var(--p-surface-400);
  cursor: pointer;
  transition: color 0.15s ease;
  padding: 0;
}

.password-toggle:hover {
  color: var(--p-surface-600);
}

.password-toggle i {
  font-size: 0.875rem;
}

.field-error {
  font-size: 0.75rem;
  color: var(--p-red-500, #ef4444);
  line-height: 1.4;
}

.general-error {
  font-size: 0.875rem;
  color: var(--p-red-500, #ef4444);
  text-align: center;
}

.submit-btn {
  margin-top: 0.5rem;
}

.auth-footer {
  margin: 0;
  margin-top: 1.5rem;
  font-size: 0.875rem;
  text-align: center;
  color: var(--p-surface-500);
}

.auth-link {
  color: var(--p-primary-color);
  text-decoration: none;
  font-weight: 500;
}

.auth-link:hover {
  text-decoration: underline;
}
</style>
