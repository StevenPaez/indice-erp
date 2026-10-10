<template>
  <div class="auth-page">
    <div class="auth-card">
      <div class="auth-header">
        <h1 class="auth-title">Recuperar contraseña</h1>
        <p class="auth-subtitle">
          Ingresa tu correo y enviaremos instrucciones si la cuenta existe.
        </p>
      </div>

      <form class="auth-form" @submit.prevent="handleSubmit">
        <div class="field-group">
          <label for="forgot-email" class="field-label">Correo</label>
          <InputText
            id="forgot-email"
            v-model="email"
            type="email"
            autocomplete="email"
            class="w-full"
            :class="{ 'p-invalid': fieldErrors.email }"
          />
          <small v-if="fieldErrors.email" class="field-error">{{ fieldErrors.email }}</small>
        </div>

        <Button type="submit" label="Enviar instrucciones" :loading="loading" class="w-full submit-btn" />
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
import { authService } from '@/api/services/authService';

const email = ref('');
const loading = ref(false);
const generalError = ref(null);
const successMessage = ref(null);
const fieldErrors = ref({});

async function handleSubmit() {
  loading.value = true;
  generalError.value = null;
  successMessage.value = null;
  fieldErrors.value = {};

  try {
    const data = await authService.forgotPassword(email.value);
    successMessage.value = data.message;
  } catch (error) {
    if (error.response?.data?.errors) {
      fieldErrors.value = Object.fromEntries(
        Object.entries(error.response.data.errors).map(([key, messages]) => [
          key,
          Array.isArray(messages) ? messages[0] : messages,
        ]),
      );
    }
    generalError.value = error.response?.data?.message || 'No fue posible procesar la solicitud.';
  } finally {
    loading.value = false;
  }
}
</script>
