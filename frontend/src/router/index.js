import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';

const routes = [
    {
        path: '/',
        redirect: '/login',
    },
    {
        path: '/login',
        name: 'login',
        component: () => import('@/views/auth/LoginView.vue'),
        meta: { guest: true },
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('@/views/auth/RegisterView.vue'),
        meta: { guest: true },
    },
    {
        path: '/books',
        name: 'books',
        component: () => import('@/views/books/BookListView.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/books/create',
        name: 'book-create',
        component: () => import('@/views/books/BookFormView.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/books/:id/edit',
        name: 'book-edit',
        component: () => import('@/views/books/BookFormView.vue'),
        meta: { requiresAuth: true },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    const authStore = useAuthStore();

    await authStore.fetchUser();

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        return '/login';
    }

    if (to.meta.guest && authStore.isAuthenticated) {
        return '/books';
    }

    return true;
});

export default router;
