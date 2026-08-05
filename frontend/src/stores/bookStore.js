import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { bookService } from '@/api/services/bookService';

export const useBookStore = defineStore('books', () => {
    const books = ref([]);
    const currentBook = ref(null);
    const loading = ref(false);
    const error = ref(null);
    const pagination = ref({
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
    });

    const lowStockBooks = computed(() =>
        books.value.filter((b) => b.stock <= 10)
    );

    const hasBooks = computed(() => books.value.length > 0);

    async function fetchBooks(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await bookService.getAll(params);
            books.value = response.data;
            pagination.value = {
                current_page: response.meta.current_page,
                last_page: response.meta.last_page,
                per_page: response.meta.per_page,
                total: response.meta.total,
            };
        } catch (e) {
            error.value = e.response?.data?.message || e.message;
        } finally {
            loading.value = false;
        }
    }

    async function fetchBook(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await bookService.getOne(id);
            currentBook.value = response.data;
            return response.data;
        } catch (e) {
            error.value = e.response?.data?.message || e.message;
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function createBook(payload) {
        const response = await bookService.create(payload);
        books.value.unshift(response.data);
        return response.data;
    }

    async function updateBook(id, payload) {
        const response = await bookService.update(id, payload);
        const index = books.value.findIndex((b) => b.id === id);
        if (index !== -1) books.value[index] = response.data;
        return response.data;
    }

    async function deleteBook(id) {
        await bookService.delete(id);
        books.value = books.value.filter((b) => b.id !== id);
    }

    async function fetchLowStock() {
        loading.value = true;
        try {
            return await bookService.getLowStock();
        } finally {
            loading.value = false;
        }
    }

    return {
        books,
        currentBook,
        loading,
        error,
        pagination,
        lowStockBooks,
        hasBooks,
        fetchBooks,
        fetchBook,
        createBook,
        updateBook,
        deleteBook,
        fetchLowStock,
    };
});
