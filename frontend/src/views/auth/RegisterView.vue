<template>
  <div class="auth-page">
    <Card class="auth-card">
      <template #title>
        <div class="text-center mb-2">
          <h2 class="text-2xl font-semibold text-surface-900">Indice ERP</h2>
        </div>
      </template>
      <template #subtitle>
        <div class="text-center text-surface-500 mb-6">Crear cuenta</div>
      </template>
      <template #content>
        <div class="flex flex-col gap-5">
          <div class="flex flex-col gap-2">
            <FloatLabel>
              <InputText
                id="name"
                v-model="name"
                class="w-full"
                :class="{ 'p-invalid': fieldErrors.name }"
              />
              <label for="name">Nombre</label>
            </FloatLabel>
            <small v-if="fieldErrors.name" class="text-red-500">{{ fieldErrors.name }}</small>
          </div>

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

            <!-- Password requirements checklist -->
            <div class="password-requirements mt-1">
              <div
                class="requirement-item"
                :class="{ met: hasMinLength }"
              >
                <i :class="hasMinLength ? 'pi pi-check-circle text-green-500' : 'pi pi-circle text-surface-400'"></i>
                <span>Mínimo 8 caracteres</span>
              </div>
              <div
                class="requirement-item"
                :class="{ met: hasUppercase }"
              >
                <i :class="hasUppercase ? 'pi pi-check-circle text-green-500' : 'pi pi-circle text-surface-400'"></i>
                <span>Al menos una mayúscula <span class="text-surface-400 text-xs">(recomendado)</span></span>
              </div>
              <div
                class="requirement-item"
                :class="{ met: hasNumber }"
              >
                <i :class="hasNumber ? 'pi pi-check-circle text-green-500' : 'pi pi-circle text-surface-400'"></i>
                <span>Al menos un número <span class="text-surface-400 text-xs">(recomendado)</span></span>
              </div>
            </div>
          </div>

          <div class="flex flex-col gap-2">
            <FloatLabel>
              <InputText
                id="password_confirmation"
                v-model="passwordConfirmation"
                type="password"
                class="w-full"
                :class="{ 'p-invalid': fieldErrors.password_confirmation }"
              />
              <label for="password_confirmation">Confirmar contraseña</label>
            </FloatLabel>
            <small v-if="fieldErrors.password_confirmation" class="text-red-500">{{ fieldErrors.password_confirmation }}</small>
          </div>

          <Button
            label="Registrarse"
            icon="pi pi-user-plus"
            @click="handleRegister"
            :loading="loading"
            class="w-full mt-2"
          />

          <div v-if="generalError" class="text-red-500 text-sm text-center">{{ generalError }}</div>

          <div class="text-center text-sm text-surface-500">
            ¿Ya tienes cuenta?
            <router-link to="/login" class="text-primary hover:underline">Iniciar sesión</router-link>
          </div>
        </div>
      </template>
    </Card>
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
const loading = ref(false);
const generalError = ref(null);
const fieldErrors = ref({});

const hasMinLength = computed(() => password.value.length >= 8);
const hasUppercase = computed(() => /[A-Z]/.test(password.value));
const hasNumber = computed(() => /[0-9]/.test(password.value));

async function handleRegister() {
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
.password-requirements {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  padding-left: 0.25rem;
}
.requirement-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8rem;
  color: var(--p-surface-500);
  transition: color 0.2s ease;
}
.requirement-item.met {
  color: var(--p-green-600);
}
.requirement-item i {
  font-size: 0.85rem;
}
</style>
