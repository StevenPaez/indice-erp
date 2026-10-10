<template>
  <div class="p-4">
    <Toolbar class="mb-4">
      <template #start>
        <h2 class="m-0">Auditoría</h2>
      </template>
      <template #end>
        <Button label="Usuarios" icon="pi pi-users" severity="secondary" @click="router.push('/users')" />
        <Button label="Libros" icon="pi pi-book" severity="secondary" class="ml-2" @click="router.push('/books')" />
      </template>
    </Toolbar>

    <Card>
      <template #content>
        <div class="audit-filters mb-4">
          <Select
            v-model="filters.event"
            :options="eventOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Todos los eventos"
            showClear
          />
          <InputText v-model="filters.actor_id" type="number" min="1" placeholder="ID del actor" />
          <InputText v-model="filters.request_id" placeholder="ID de solicitud" />
          <InputText v-model="filters.date_from" type="date" aria-label="Fecha inicial" />
          <InputText v-model="filters.date_to" type="date" aria-label="Fecha final" />
          <Button label="Filtrar" icon="pi pi-search" @click="applyFilters" />
          <Button label="Limpiar" severity="secondary" @click="clearFilters" />
        </div>

        <div v-if="auditLogStore.error" class="general-error mb-3">{{ auditLogStore.error }}</div>

        <DataTable
          :value="auditLogStore.auditLogs"
          :loading="auditLogStore.loading"
          paginator
          lazy
          stripedRows
          showGridlines
          :rows="pagination.per_page"
          :totalRecords="pagination.total"
          :rowsPerPageOptions="[5, 10, 25, 50]"
          @page="onPage"
        >
          <template #empty>No hay eventos que coincidan con los filtros.</template>
          <Column field="created_at" header="Fecha">
            <template #body="slotProps">{{ formatDate(slotProps.data.created_at) }}</template>
          </Column>
          <Column field="event" header="Evento">
            <template #body="slotProps">{{ eventLabel(slotProps.data.event) }}</template>
          </Column>
          <Column header="Actor">
            <template #body="slotProps">
              <template v-if="slotProps.data.actor">
                {{ slotProps.data.actor.name }}<br />
                <small>{{ slotProps.data.actor.email }} · #{{ slotProps.data.actor.id }}</small>
              </template>
              <span v-else>Sistema</span>
            </template>
          </Column>
          <Column header="Recurso">
            <template #body="slotProps">
              <span v-if="slotProps.data.subject">
                {{ slotProps.data.subject.type }} #{{ slotProps.data.subject.id }}
              </span>
              <span v-else>—</span>
            </template>
          </Column>
          <Column header="Detalle">
            <template #body="slotProps">{{ formatMetadata(slotProps.data.metadata) }}</template>
          </Column>
          <Column field="request_id" header="Solicitud">
            <template #body="slotProps">
              <code>{{ slotProps.data.request_id || '—' }}</code>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useAuditLogStore } from '@/stores/auditLogStore';

const router = useRouter();
const auditLogStore = useAuditLogStore();
const filters = reactive({
  event: null,
  actor_id: '',
  request_id: '',
  date_from: '',
  date_to: '',
});
const query = reactive({ page: 1, per_page: 25 });
const pagination = computed(() => auditLogStore.pagination);

const eventOptions = [
  { label: 'Administrador inicial configurado', value: 'system.admin_bootstrapped' },
  { label: 'Usuario creado', value: 'user.created' },
  { label: 'Usuario actualizado', value: 'user.updated' },
  { label: 'Rol modificado', value: 'user.role_changed' },
  { label: 'Usuario activado', value: 'user.activated' },
  { label: 'Usuario desactivado', value: 'user.deactivated' },
  { label: 'Contraseña modificada', value: 'user.password_changed' },
  { label: 'Inicio de sesión correcto', value: 'auth.login_succeeded' },
  { label: 'Inicio de sesión fallido', value: 'auth.login_failed' },
  { label: 'Cierre de sesión', value: 'auth.logout' },
  { label: 'Recuperación solicitada', value: 'auth.password_reset_requested' },
  { label: 'Recuperación completada', value: 'auth.password_reset_completed' },
  { label: 'Autorización denegada', value: 'authorization.denied' },
];

async function loadAuditLogs() {
  const params = { ...query };
  for (const [key, value] of Object.entries(filters)) {
    if (value !== '' && value !== null) params[key] = value;
  }

  try {
    await auditLogStore.fetchAuditLogs(params);
  } catch {
    // The store exposes the request error on this surface.
  }
}

function applyFilters() {
  query.page = 1;
  loadAuditLogs();
}

function clearFilters() {
  Object.assign(filters, {
    event: null,
    actor_id: '',
    request_id: '',
    date_from: '',
    date_to: '',
  });
  query.page = 1;
  loadAuditLogs();
}

function onPage(event) {
  query.page = event.page + 1;
  query.per_page = event.rows;
  loadAuditLogs();
}

function eventLabel(event) {
  return eventOptions.find((option) => option.value === event)?.label || event;
}

function formatDate(value) {
  return new Intl.DateTimeFormat('es', {
    dateStyle: 'short',
    timeStyle: 'medium',
  }).format(new Date(value));
}

function formatMetadata(metadata) {
  return metadata ? JSON.stringify(metadata) : '—';
}

onMounted(loadAuditLogs);
</script>

<style scoped>
.audit-filters {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr));
  gap: 0.75rem;
  align-items: center;
}

code {
  font-size: 0.75rem;
  overflow-wrap: anywhere;
}
</style>
