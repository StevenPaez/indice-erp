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
        path: '/forgot-password',
        name: 'forgot-password',
        component: () => import('@/views/auth/ForgotPasswordView.vue'),
        meta: { guest: true },
    },
    {
        path: '/reset-password',
        name: 'reset-password',
        component: () => import('@/views/auth/ResetPasswordView.vue'),
        meta: { guest: true },
    },
    {
        path: '/change-password',
        name: 'change-password',
        component: () => import('@/views/auth/ChangePasswordView.vue'),
        meta: { requiresAuth: true },
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
        meta: { requiresAuth: true, capability: 'catalog.manage' },
    },
    {
        path: '/books/:id/edit',
        name: 'book-edit',
        component: () => import('@/views/books/BookFormView.vue'),
        meta: { requiresAuth: true, capability: 'catalog.manage' },
    },
    {
        path: '/users',
        name: 'users',
        component: () => import('@/views/users/UserListView.vue'),
        meta: { requiresAuth: true, capability: 'users.view' },
    },
    {
        path: '/audit-logs',
        name: 'audit-logs',
        component: () => import('@/views/audit/AuditLogListView.vue'),
        meta: { requiresAuth: true, capability: 'audit.view' },
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

    if (
        authStore.isAuthenticated
        && authStore.user.must_change_password
        && to.name !== 'change-password'
    ) {
        return '/change-password';
    }
    if (to.meta.capability && !authStore.hasCapability(to.meta.capability)) {
        return '/books';
    }


    if (to.meta.guest && authStore.isAuthenticated) {
        return '/books';
    }

    return true;
});

export default router;
