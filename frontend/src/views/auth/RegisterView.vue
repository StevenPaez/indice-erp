<template>
  <div class="auth-page">
    <div class="auth-card">
      <div class="auth-header">
        <h1 class="auth-title">Crear cuenta</h1>
        <p class="auth-subtitle">Completa el formulario para registrarte</p>
      </div>

      <form class="auth-form" @submit.prevent="handleRegister">
        <div class="field-group">
          <label for="register-name" class="field-label">Nombre</label>
          <InputText
            id="register-name"
            v-model="name"
            type="text"
            autocomplete="name"
            placeholder="Tu nombre"
            class="w-full"
            :class="{ 'p-invalid': fieldErrors.name }"
          />
          <small v-if="fieldErrors.name" class="field-error">{{ fieldErrors.name }}</small>
        </div>

        <div class="field-group">
          <label for="register-email" class="field-label">Correo</label>
          <InputText
            id="register-email"
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
          <label for="register-password" class="field-label">Contraseña</label>
          <div class="password-wrapper">
            <InputText
              id="register-password"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              autocomplete="new-password"
              placeholder="Crea una contraseña"
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

          <ul class="requirements-list">
            <li class="requirement-item" :class="{ met: checks.length }">
              <span class="requirement-icon" :class="checks.length ? 'icon-met' : 'icon-pending'">
                <i :class="checks.length ? 'pi pi-check' : 'pi pi-times'"></i>
              </span>
              <span :class="checks.length ? 'text-met' : 'text-pending'">Mínimo 8 caracteres</span>
            </li>
            <li class="requirement-item" :class="{ met: checks.upper }">
              <span class="requirement-icon" :class="checks.upper ? 'icon-met' : 'icon-pending'">
                <i :class="checks.upper ? 'pi pi-check' : 'pi pi-times'"></i>
              </span>
              <span :class="checks.upper ? 'text-met' : 'text-pending'">Al menos una mayúscula (recomendado)</span>
            </li>
            <li class="requirement-item" :class="{ met: checks.number }">
              <span class="requirement-icon" :class="checks.number ? 'icon-met' : 'icon-pending'">
                <i :class="checks.number ? 'pi pi-check' : 'pi pi-times'"></i>
              </span>
              <span :class="checks.number ? 'text-met' : 'text-pending'">Al menos un número (recomendado)</span>
            </li>
          </ul>
        </div>

        <div class="field-group">
          <label for="register-confirm" class="field-label">Confirmar contraseña</label>
          <InputText
            id="register-confirm"
            v-model="passwordConfirmation"
            :type="showPassword ? 'text' : 'password'"
            autocomplete="new-password"
            placeholder="Repite tu contraseña"
            class="w-full"
            :class="{ 'p-invalid': passwordsDontMatch || fieldErrors.password_confirmation }"
          />
          <small v-if="passwordsDontMatch" class="field-error">Las contraseñas no coinciden</small>
          <small v-if="fieldErrors.password_confirmation" class="field-error">{{ fieldErrors.password_confirmation }}</small>
        </div>

        <Button
          type="submit"
          label="Crear cuenta"
          :loading="loading"
          :disabled="!canSubmit"
          class="w-full submit-btn"
        />

        <div v-if="generalError" class="general-error">{{ generalError }}</div>
      </form>

      <p class="auth-footer">
        ¿Ya tienes cuenta?
        <router-link to="/login" class="auth-link">Iniciar sesión</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';

const router = useRouter();
const authStore = useAuthStore();

const name = ref('');
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const showPassword = ref(false);
const loading = ref(false);
const generalError = ref(null);
const fieldErrors = ref({});

const checks = computed(() => ({
  length: password.value.length >= 8,
  upper: /[A-Z]/.test(password.value),
  number: /[0-9]/.test(password.value),
}));

const passwordsDontMatch = computed(
  () => passwordConfirmation.value.length > 0 && password.value !== passwordConfirmation.value
);

const canSubmit = computed(
  () => checks.value.length && name.value.trim() !== '' && email.value.trim() !== '' && !passwordsDontMatch.value
);

async function handleRegister() {
  if (!canSubmit.value) return;

  loading.value = true;
  generalError.value = null;
  fieldErrors.value = {};

  try {
    await authStore.register({
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    });
    await authStore.login({ email: email.value, password: password.value });
    router.push('/books');
  } catch (e) {
    if (e.response?.data?.errors) {
      const errors = e.response.data.errors;
      fieldErrors.value = Object.fromEntries(
        Object.entries(errors).map(([key, msgs]) => [key, Array.isArray(msgs) ? msgs[0] : msgs])
      );
    }
    generalError.value = e.response?.data?.message || 'Error al registrarse';
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

.requirements-list {
  margin: 0.25rem 0 0 0;
  padding: 0;
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.requirement-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8rem;
  transition: color 0.15s ease;
}

.requirement-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 1rem;
  height: 1rem;
  border-radius: 50%;
  flex-shrink: 0;
  transition: background-color 0.15s ease, color 0.15s ease;
}

.requirement-icon i {
  font-size: 0.6rem;
}

.icon-met {
  background-color: var(--p-primary-color);
  color: var(--p-primary-contrast-color, #fff);
}

.icon-pending {
  background-color: var(--p-surface-200, #e5e7eb);
  color: var(--p-surface-400);
}

.text-met {
  color: var(--p-surface-900);
}

.text-pending {
  color: var(--p-surface-400);
}
</style>
