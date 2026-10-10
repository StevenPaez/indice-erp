<template>
  <div class="p-4">
    <Toolbar class="mb-4">
      <template #start>
        <h2 class="m-0">Usuarios</h2>
      </template>
      <template #end>
        <Button v-if="authStore.hasCapability('audit.view')" label="Auditoría" icon="pi pi-shield" severity="secondary" @click="router.push('/audit-logs')" />
        <Button label="Libros" icon="pi pi-book" severity="secondary" class="ml-2" @click="router.push('/books')" />
        <Button label="Nuevo usuario" icon="pi pi-user-plus" class="ml-2" @click="openCreateDialog" />
      </template>
    </Toolbar>

    <Card>
      <template #content>
        <div class="flex gap-3 mb-4 user-filters">
          <InputText
            v-model="filters.search"
            placeholder="Buscar por nombre o correo"
            class="flex-1"
            @keyup.enter="applyFilters"
          />
          <Select
            v-model="filters.role"
            :options="roleFilterOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Todos los roles"
            showClear
          />
          <Select
            v-model="filters.is_active"
            :options="statusFilterOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Todos los estados"
            showClear
          />
          <Button label="Filtrar" icon="pi pi-search" @click="applyFilters" />
        </div>

        <div v-if="userStore.error" class="general-error mb-3">{{ userStore.error }}</div>

        <DataTable
          :value="userStore.users"
          :loading="userStore.loading"
          paginator
          lazy
          stripedRows
          showGridlines
          :rows="pagination.per_page"
          :totalRecords="pagination.total"
          :rowsPerPageOptions="[5, 10, 15, 25]"
          @page="onPage"
          @sort="onSort"
        >
          <template #empty>No hay usuarios que coincidan con los filtros.</template>
          <Column field="name" header="Nombre" sortable />
          <Column field="email" header="Correo" sortable />
          <Column field="role" header="Rol" sortable>
            <template #body="slotProps">{{ roleLabel(slotProps.data.role) }}</template>
          </Column>
          <Column field="is_active" header="Estado" sortable>
            <template #body="slotProps">
              <Tag
                :value="slotProps.data.is_active ? 'Activo' : 'Inactivo'"
                :severity="slotProps.data.is_active ? 'success' : 'danger'"
              />
            </template>
          </Column>
          <Column header="Credencial">
            <template #body="slotProps">
              <Tag
                :value="slotProps.data.must_change_password ? 'Temporal' : 'Definitiva'"
                :severity="slotProps.data.must_change_password ? 'warn' : 'secondary'"
              />
            </template>
          </Column>
          <Column header="Acciones">
            <template #body="slotProps">
              <Button
                icon="pi pi-pencil"
                aria-label="Editar identidad"
                severity="info"
                text
                rounded
                @click="openEditDialog(slotProps.data)"
              />
              <Button
                icon="pi pi-id-card"
                aria-label="Cambiar rol"
                severity="secondary"
                text
                rounded
                :disabled="slotProps.data.id === authStore.user.id"
                @click="openRoleDialog(slotProps.data)"
              />
              <Button
                :icon="slotProps.data.is_active ? 'pi pi-user-minus' : 'pi pi-user-plus'"
                :aria-label="slotProps.data.is_active ? 'Desactivar usuario' : 'Activar usuario'"
                :severity="slotProps.data.is_active ? 'danger' : 'success'"
                text
                rounded
                @click="confirmStatusChange(slotProps.data)"
              />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <Dialog
      v-model:visible="identityDialogVisible"
      modal
      :header="editingUser ? 'Editar usuario' : 'Nuevo usuario'"
      :style="{ width: '32rem' }"
      @hide="resetIdentityForm"
    >
      <form class="auth-form" @submit.prevent="saveIdentity">
        <div class="field-group">
          <label for="user-name" class="field-label">Nombre</label>
          <InputText id="user-name" v-model="identityForm.name" class="w-full" :class="{ 'p-invalid': fieldErrors.name }" />
          <small v-if="fieldErrors.name" class="field-error">{{ fieldErrors.name }}</small>
        </div>
        <div class="field-group">
          <label for="user-email" class="field-label">Correo</label>
          <InputText id="user-email" v-model="identityForm.email" type="email" class="w-full" :class="{ 'p-invalid': fieldErrors.email }" />
          <small v-if="fieldErrors.email" class="field-error">{{ fieldErrors.email }}</small>
        </div>

        <template v-if="!editingUser">
          <div class="field-group">
            <label for="user-role" class="field-label">Rol</label>
            <Select id="user-role" v-model="identityForm.role" :options="roleOptions" optionLabel="label" optionValue="value" class="w-full" />
            <small v-if="fieldErrors.role" class="field-error">{{ fieldErrors.role }}</small>
          </div>
          <div class="field-group">
            <label for="user-status" class="field-label">Estado inicial</label>
            <Select id="user-status" v-model="identityForm.is_active" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
          </div>
          <div class="field-group">
            <label for="user-password" class="field-label">Contraseña temporal</label>
            <InputText id="user-password" v-model="identityForm.password" type="password" autocomplete="new-password" class="w-full" :class="{ 'p-invalid': fieldErrors.password }" />
            <small v-if="fieldErrors.password" class="field-error">{{ fieldErrors.password }}</small>
          </div>
          <div class="field-group">
            <label for="user-password-confirmation" class="field-label">Confirmar contraseña</label>
            <InputText id="user-password-confirmation" v-model="identityForm.password_confirmation" type="password" autocomplete="new-password" class="w-full" />
          </div>
        </template>

        <div v-if="formError" class="general-error">{{ formError }}</div>
        <div class="flex justify-content-end gap-2 mt-3">
          <Button type="button" label="Cancelar" severity="secondary" @click="identityDialogVisible = false" />
          <Button type="submit" label="Guardar" :loading="saving" />
        </div>
      </form>
    </Dialog>

    <Dialog v-model:visible="roleDialogVisible" modal header="Cambiar rol" :style="{ width: '28rem' }">
      <div class="field-group">
        <label for="new-role" class="field-label">Rol de {{ selectedUser?.name }}</label>
        <Select id="new-role" v-model="selectedRole" :options="roleOptions" optionLabel="label" optionValue="value" class="w-full" />
      </div>
      <div v-if="formError" class="general-error mt-3">{{ formError }}</div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="roleDialogVisible = false" />
        <Button label="Guardar rol" :loading="saving" @click="saveRole" />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { useAuthStore } from '@/stores/authStore';
import { useUserStore } from '@/stores/userStore';

const router = useRouter();
const confirm = useConfirm();
const toast = useToast();
const authStore = useAuthStore();
const userStore = useUserStore();

const roleOptions = [
  { label: 'Administrador', value: 'admin' },
  { label: 'Operador', value: 'operator' },
  { label: 'Consulta', value: 'viewer' },
];
const roleFilterOptions = roleOptions;
const statusOptions = [
  { label: 'Activo', value: true },
  { label: 'Inactivo', value: false },
];
const statusFilterOptions = statusOptions;
const filters = reactive({ search: '', role: null, is_active: null });
const query = reactive({ page: 1, per_page: 15, sort_by: 'name', sort_dir: 'asc' });
const identityDialogVisible = ref(false);
const roleDialogVisible = ref(false);
const editingUser = ref(null);
const selectedUser = ref(null);
const selectedRole = ref(null);
const saving = ref(false);
const fieldErrors = ref({});
const formError = ref(null);
const identityForm = reactive(emptyIdentityForm());
const pagination = computed(() => userStore.pagination);

function emptyIdentityForm() {
  return {
    name: '',
    email: '',
    role: 'viewer',
    is_active: true,
    password: '',
    password_confirmation: '',
  };
}

async function loadUsers() {
  const params = {
    ...query,
    search: filters.search || undefined,
    role: filters.role || undefined,
  };
  if (filters.is_active !== null) params.is_active = filters.is_active ? 1 : 0;

  try {
    await userStore.fetchUsers(params);
  } catch {
    // The store exposes the user-facing loading error.
  }
}

function applyFilters() {
  query.page = 1;
  loadUsers();
}

function onPage(event) {
  query.page = event.page + 1;
  query.per_page = event.rows;
  loadUsers();
}

function onSort(event) {
  query.sort_by = event.sortField;
  query.sort_dir = event.sortOrder === 1 ? 'asc' : 'desc';
  loadUsers();
}

function openCreateDialog() {
  resetIdentityForm();
  identityDialogVisible.value = true;
}

function openEditDialog(user) {
  resetIdentityForm();
  editingUser.value = user;
  identityForm.name = user.name;
  identityForm.email = user.email;
  identityDialogVisible.value = true;
}

function resetIdentityForm() {
  Object.assign(identityForm, emptyIdentityForm());
  editingUser.value = null;
  fieldErrors.value = {};
  formError.value = null;
}

async function saveIdentity() {
  saving.value = true;
  fieldErrors.value = {};
  formError.value = null;

  try {
    if (editingUser.value) {
      await userStore.updateUser(editingUser.value.id, {
        name: identityForm.name,
        email: identityForm.email,
      });
    } else {
      await userStore.createUser({ ...identityForm });
      await loadUsers();
    }
    identityDialogVisible.value = false;
    toast.add({ severity: 'success', summary: 'Guardado', detail: 'Usuario guardado correctamente.', life: 3000 });
  } catch (error) {
    setFormErrors(error);
  } finally {
    saving.value = false;
  }
}

function openRoleDialog(user) {
  selectedUser.value = user;
  selectedRole.value = user.role;
  formError.value = null;
  roleDialogVisible.value = true;
}

async function saveRole() {
  saving.value = true;
  formError.value = null;
  try {
    await userStore.changeRole(selectedUser.value.id, selectedRole.value);
    roleDialogVisible.value = false;
    toast.add({ severity: 'success', summary: 'Rol actualizado', detail: 'El rol fue actualizado.', life: 3000 });
  } catch (error) {
    formError.value = error.response?.data?.message || 'No fue posible cambiar el rol.';
  } finally {
    saving.value = false;
  }
}

function confirmStatusChange(user) {
  const nextStatus = !user.is_active;
  confirm.require({
    header: nextStatus ? 'Activar usuario' : 'Desactivar usuario',
    message: `¿Confirmas que deseas ${nextStatus ? 'activar' : 'desactivar'} a ${user.name}?`,
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      try {
        await userStore.changeStatus(user.id, nextStatus);
        toast.add({ severity: 'success', summary: 'Estado actualizado', detail: 'El estado fue actualizado.', life: 3000 });
      } catch (error) {
        toast.add({ severity: 'error', summary: 'No fue posible actualizar', detail: error.response?.data?.message || 'Intenta nuevamente.', life: 5000 });
      }
    },
  });
}

function setFormErrors(error) {
  const errors = error.response?.data?.errors || {};
  fieldErrors.value = Object.fromEntries(
    Object.entries(errors).map(([key, messages]) => [key, Array.isArray(messages) ? messages[0] : messages]),
  );
  formError.value = error.response?.data?.message || 'No fue posible guardar el usuario.';
}

function roleLabel(role) {
  return roleOptions.find((option) => option.value === role)?.label || role;
}

onMounted(loadUsers);
</script>

<style scoped>
.user-filters {
  align-items: center;
  flex-wrap: wrap;
}

.user-filters .p-select {
  min-width: 12rem;
}
</style>
