<template>
  <div class="auth-page">
    <div class="auth-card">
      <div class="auth-header">
        <h1 class="auth-title">Restablecer contraseña</h1>
        <p class="auth-subtitle">Define una contraseña nueva de al menos 15 caracteres.</p>
      </div>

      <form class="auth-form" @submit.prevent="handleSubmit">
        <div class="field-group">
          <label for="reset-email" class="field-label">Correo</label>
          <InputText
            id="reset-email"
            v-model="email"
            type="email"
            autocomplete="email"
            class="w-full"
            :class="{ 'p-invalid': fieldErrors.email }"
          />
          <small v-if="fieldErrors.email" class="field-error">{{ fieldErrors.email }}</small>
        </div>

        <div class="field-group">
          <label for="reset-password" class="field-label">Nueva contraseña</label>
          <InputText
            id="reset-password"
            v-model="password"
            type="password"
            autocomplete="new-password"
            class="w-full"
            :class="{ 'p-invalid': fieldErrors.password }"
          />
          <small v-if="fieldErrors.password" class="field-error">{{ fieldErrors.password }}</small>
        </div>

        <div class="field-group">
          <label for="reset-confirmation" class="field-label">Confirmar contraseña</label>
          <InputText
            id="reset-confirmation"
            v-model="passwordConfirmation"
            type="password"
            autocomplete="new-password"
            class="w-full"
          />
        </div>

        <Button type="submit" label="Restablecer contraseña" :loading="loading" class="w-full submit-btn" />
        <div v-if="generalError" class="general-error">{{ generalError }}</div>
        <div v-if="successMessage" class="general-success">{{ successMessage }}</div>
      </form>

      <p class="auth-footer">
        <router-link to="/login" class="auth-link">Volver al inicio de sesión</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRoute } from 'vue-router';
import { authService } from '@/api/services/authService';

const route = useRoute();
const email = ref(String(route.query.email || ''));
const password = ref('');
const passwordConfirmation = ref('');
const loading = ref(false);
const generalError = ref(route.query.token ? null : 'El enlace de recuperación no es válido.');
const successMessage = ref(null);
const fieldErrors = ref({});

async function handleSubmit() {
  if (!route.query.token) return;

  loading.value = true;
  generalError.value = null;
  successMessage.value = null;
  fieldErrors.value = {};

  try {
    const data = await authService.resetPassword({
      token: String(route.query.token),
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    });
    successMessage.value = data.message;
    password.value = '';
    passwordConfirmation.value = '';
  } catch (error) {
    if (error.response?.data?.errors) {
      fieldErrors.value = Object.fromEntries(
        Object.entries(error.response.data.errors).map(([key, messages]) => [
          key,
          Array.isArray(messages) ? messages[0] : messages,
        ]),
      );
    }
    generalError.value = error.response?.data?.message || 'No fue posible restablecer la contraseña.';
  } finally {
    loading.value = false;
  }
}
</script>
