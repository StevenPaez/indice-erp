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
        <router-link to="/forgot-password" class="auth-link">
          ¿Olvidaste tu contraseña?
        </router-link>
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
    const user = await authStore.login({ email: email.value, password: password.value });
    router.push(user.must_change_password ? '/change-password' : '/books');
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

