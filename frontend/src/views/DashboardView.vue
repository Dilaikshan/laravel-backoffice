<template>
  <v-app-bar app color="primary" dark>
    <v-app-bar-title>Laravel Backoffice</v-app-bar-title>
    <v-spacer></v-spacer>
    <v-btn icon @click="refreshPosts">
      <v-icon>mdi-refresh</v-icon>
    </v-btn>
    <v-btn text @click="handleLogout">
      Logout <v-icon right>mdi-logout</v-icon>
    </v-btn>
  </v-app-bar>

  <v-main>
    <v-container fluid>
      <router-view />
    </v-container>
  </v-main>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useBlogPostsStore } from '@/stores/blogPosts';

const router = useRouter();
const authStore = useAuthStore();
const blogPostsStore = useBlogPostsStore(); // To trigger a refresh from the dashboard

const handleLogout = async () => {
  await authStore.logout();
  router.push({ name: 'login' });
};

const refreshPosts = () => {
  blogPostsStore.fetchPosts(true); // Re-fetch posts, potentially with priority sorting
};
</script>