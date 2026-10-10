<template>
  <div class="auth-page">
    <div class="auth-card">
      <div class="auth-header">
        <h1 class="auth-title">Cambiar contraseña</h1>
        <p class="auth-subtitle">
          Confirma tu contraseña actual y define una nueva de al menos 15 caracteres.
        </p>
      </div>

      <form class="auth-form" @submit.prevent="handleSubmit">
        <div class="field-group">
          <label for="current-password" class="field-label">Contraseña actual</label>
          <InputText
            id="current-password"
            v-model="currentPassword"
            type="password"
            autocomplete="current-password"
            class="w-full"
            :class="{ 'p-invalid': fieldErrors.current_password }"
          />
          <small v-if="fieldErrors.current_password" class="field-error">
            {{ fieldErrors.current_password }}
          </small>
        </div>

        <div class="field-group">
          <label for="new-password" class="field-label">Nueva contraseña</label>
          <InputText
            id="new-password"
            v-model="password"
            type="password"
            autocomplete="new-password"
            class="w-full"
            :class="{ 'p-invalid': fieldErrors.password }"
          />
          <small v-if="fieldErrors.password" class="field-error">{{ fieldErrors.password }}</small>
        </div>

        <div class="field-group">
          <label for="new-password-confirmation" class="field-label">Confirmar contraseña</label>
          <InputText
            id="new-password-confirmation"
            v-model="passwordConfirmation"
            type="password"
            autocomplete="new-password"
            class="w-full"
          />
        </div>

        <Button type="submit" label="Guardar contraseña" :loading="loading" class="w-full submit-btn" />
        <Button type="button" label="Cerrar sesión" severity="secondary" text class="w-full" @click="handleLogout" />
        <div v-if="generalError" class="general-error">{{ generalError }}</div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';

const router = useRouter();
const authStore = useAuthStore();
const currentPassword = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const loading = ref(false);
const generalError = ref(null);
const fieldErrors = ref({});

async function handleSubmit() {
  loading.value = true;
  generalError.value = null;
  fieldErrors.value = {};

  try {
    await authStore.changePassword({
      current_password: currentPassword.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    });
    router.push('/books');
  } catch (error) {
    if (error.response?.data?.errors) {
      fieldErrors.value = Object.fromEntries(
        Object.entries(error.response.data.errors).map(([key, messages]) => [
          key,
          Array.isArray(messages) ? messages[0] : messages,
        ]),
      );
    }
    generalError.value = error.response?.data?.message || 'No fue posible cambiar la contraseña.';
  } finally {
    loading.value = false;
  }
}

async function handleLogout() {
  await authStore.logout();
  router.push('/login');
}
</script>
