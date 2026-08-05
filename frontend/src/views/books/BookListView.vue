<template>
  <div class="p-4">
    <Toolbar class="mb-4">
      <template #start>
        <h2 class="m-0">Libros</h2>
      </template>
      <template #end>
        <Button label="Nuevo Libro" icon="pi pi-plus" @click="$router.push('/books/create')" />
        <Button label="Cerrar Sesion" icon="pi pi-sign-out" severity="secondary" class="ml-2" @click="handleLogout" />
      </template>
    </Toolbar>

    <Card>
      <template #content>
        <div class="flex gap-3 mb-4">
          <InputText v-model="search" placeholder="Buscar por titulo, autor o ISBN..." class="flex-1" @keyup.enter="searchBooks" />
          <Button icon="pi pi-search" @click="searchBooks" />
        </div>

        <DataTable :value="bookStore.books" :loading="bookStore.loading" paginator :rows="15"
          :totalRecords="bookStore.pagination.total" :rowsPerPageOptions="[5, 10, 15, 25]"
          @page="onPage" @sort="onSort" :lazy="true"
          stripedRows showGridlines>
          <Column field="title" header="Titulo" sortable />
          <Column field="author" header="Autor" sortable />
          <Column field="isbn" header="ISBN" />
          <Column field="purchase_price" header="P. Compra" sortable>
            <template #body="slotProps">${{ slotProps.data.purchase_price }}</template>
          </Column>
          <Column field="sale_price" header="P. Venta" sortable>
            <template #body="slotProps">${{ slotProps.data.sale_price }}</template>
          </Column>
          <Column field="stock" header="Stock" sortable>
            <template #body="slotProps">
              <Tag :value="slotProps.data.stock" :severity="slotProps.data.is_low_stock ? 'warn' : 'success'" />
            </template>
          </Column>
          <Column header="Acciones">
            <template #body="slotProps">
              <Button icon="pi pi-pencil" severity="info" text rounded @click="$router.push(`/books/${slotProps.data.id}/edit`)" />
              <Button icon="pi pi-trash" severity="danger" text rounded @click="confirmDelete(slotProps.data)" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useBookStore } from '@/stores/bookStore';
import { useAuthStore } from '@/stores/authStore';
import { useRouter } from 'vue-router';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';

const bookStore = useBookStore();
const authStore = useAuthStore();
const router = useRouter();
const confirm = useConfirm();
const toast = useToast();

const search = ref('');

const lazyParams = ref({
  page: 1,
  per_page: 15,
  sort_by: 'created_at',
  sort_dir: 'desc',
});

async function loadBooks() {
  await bookStore.fetchBooks({
    search: search.value || undefined,
    page: lazyParams.value.page,
    per_page: lazyParams.value.per_page,
    sort_by: lazyParams.value.sort_by,
    sort_dir: lazyParams.value.sort_dir,
  });
}

function searchBooks() {
  lazyParams.value.page = 1;
  loadBooks();
}

function onPage(event) {
  lazyParams.value.page = event.page + 1;
  lazyParams.value.per_page = event.rows;
  loadBooks();
}

function onSort(event) {
  lazyParams.value.sort_by = event.sortField;
  lazyParams.value.sort_dir = event.sortOrder === 1 ? 'asc' : 'desc';
  loadBooks();
}

function confirmDelete(book) {
  confirm.require({
    message: `Estas seguro de eliminar "${book.title}"?`,
    header: 'Confirmar eliminacion',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      await bookStore.deleteBook(book.id);
      toast.add({ severity: 'success', summary: 'Eliminado', detail: 'Libro eliminado correctamente', life: 3000 });
    },
  });
}

async function handleLogout() {
  await authStore.logout();
  router.push('/login');
}

onMounted(() => {
  loadBooks();
});
</script>
