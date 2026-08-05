import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';

const routes = [
    {
        path: '/',
        redirect: '/books',
    },
    {
        path: '/login',
        name: 'login',
        component: () => import('@/views/auth/LoginView.vue'),
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

router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        try {
            await authStore.fetchUser();
            if (!authStore.isAuthenticated) {
                return next('/login');
            }
            next();
        } catch {
            next('/login');
        }
    } else if (to.meta.guest && authStore.isAuthenticated) {
        next('/books');
    } else {
        next();
    }
});

export default router;
