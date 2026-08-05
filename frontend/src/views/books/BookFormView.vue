<template>
  <div class="p-4">
    <Toolbar class="mb-4">
      <template #start>
        <Button icon="pi pi-arrow-left" severity="secondary" @click="$router.push('/books')" />
        <h2 class="m-0 ml-3">{{ isEditing ? 'Editar Libro' : 'Nuevo Libro' }}</h2>
      </template>
    </Toolbar>

    <Card>
      <template #content>
        <div class="flex flex-col gap-4 max-w-2xl">
          <FloatLabel>
            <InputText id="title" v-model="form.title" class="w-full" />
            <label for="title">Titulo *</label>
          </FloatLabel>

          <FloatLabel>
            <InputText id="isbn" v-model="form.isbn" class="w-full" />
            <label for="isbn">ISBN *</label>
          </FloatLabel>

          <FloatLabel>
            <InputText id="author" v-model="form.author" class="w-full" />
            <label for="author">Autor *</label>
          </FloatLabel>

          <FloatLabel>
            <Textarea id="description" v-model="form.description" class="w-full" rows="3" />
            <label for="description">Descripcion</label>
          </FloatLabel>

          <div class="flex gap-4">
            <FloatLabel class="flex-1">
              <InputNumber id="purchase_price" v-model="form.purchase_price" mode="currency" currency="USD" class="w-full" />
              <label for="purchase_price">Precio Compra</label>
            </FloatLabel>
            <FloatLabel class="flex-1">
              <InputNumber id="sale_price" v-model="form.sale_price" mode="currency" currency="USD" class="w-full" />
              <label for="sale_price">Precio Venta</label>
            </FloatLabel>
          </div>

          <FloatLabel>
            <InputNumber id="stock" v-model="form.stock" class="w-full" />
            <label for="stock">Stock</label>
          </FloatLabel>

          <div class="flex gap-3 mt-4">
            <Button :label="isEditing ? 'Actualizar' : 'Crear'" icon="pi pi-check" @click="handleSubmit" :loading="loading" />
            <Button label="Cancelar" icon="pi pi-times" severity="secondary" @click="$router.push('/books')" />
          </div>

          <div v-if="error" class="text-red-500 text-sm">{{ error }}</div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useBookStore } from '@/stores/bookStore';
import { useToast } from 'primevue/usetoast';

const route = useRoute();
const router = useRouter();
const bookStore = useBookStore();
const toast = useToast();

const isEditing = computed(() => !!route.params.id);
const loading = ref(false);
const error = ref(null);

const form = ref({
  title: '',
  isbn: '',
  author: '',
  description: '',
  purchase_price: 0,
  sale_price: 0,
  stock: 0,
});

onMounted(async () => {
  if (isEditing.value) {
    try {
      const book = await bookStore.fetchBook(route.params.id);
      form.value = {
        title: book.title,
        isbn: book.isbn,
        author: book.author,
        description: book.description || '',
        purchase_price: Number(book.purchase_price) || 0,
        sale_price: Number(book.sale_price) || 0,
        stock: book.stock,
      };
    } catch (e) {
      toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar el libro', life: 3000 });
      router.push('/books');
    }
  }
});

async function handleSubmit() {
  loading.value = true;
  error.value = null;
  try {
    if (isEditing.value) {
      await bookStore.updateBook(route.params.id, form.value);
      toast.add({ severity: 'success', summary: 'Actualizado', detail: 'Libro actualizado correctamente', life: 3000 });
    } else {
      await bookStore.createBook(form.value);
      toast.add({ severity: 'success', summary: 'Creado', detail: 'Libro creado correctamente', life: 3000 });
    }
    router.push('/books');
  } catch (e) {
    error.value = e.response?.data?.message || 'Error al guardar el libro';
    if (e.response?.data?.errors) {
      error.value = Object.values(e.response.data.errors).flat().join(', ');
    }
  } finally {
    loading.value = false;
  }
}
</script>
