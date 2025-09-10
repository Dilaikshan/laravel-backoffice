import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

import LoginView from '@/views/LoginView.vue'
import DashboardView from '@/views/DashboardView.vue'
import PostListView from '@/views/BlogPosts/PostListView.vue'
import PostFormView from '@/views/BlogPosts/PostFormView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { requiresAuth: false }
    },
    {
      path: '/',
      name: 'dashboard',
      component: DashboardView,
      meta: { requiresAuth: true },
      children: [
        {
          path: '', // Default child route for /
          name: 'posts-list',
          component: PostListView,
          meta: { requiresAuth: true }
        },
        {
          path: 'posts/create',
          name: 'post-create',
          component: PostFormView,
          meta: { requiresAuth: true }
        },
        {
          path: 'posts/edit/:id',
          name: 'post-edit',
          component: PostFormView,
          meta: { requiresAuth: true }
        }
      ]
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/login' // Redirect unmatched routes to login
    }
  ]
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  // Ensure user state is loaded on refresh or direct access
  if (!authStore.user && authStore.token) {
    await authStore.fetchUser()
  }

  const requiresAuth = to.meta.requiresAuth

  if (requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'login' })
  } else if (!requiresAuth && authStore.isAuthenticated) {
    // If user is logged in and trying to access login page, redirect to dashboard
    next({ name: 'dashboard' })
  } else {
    next()
  }
})

export default router