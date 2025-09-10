<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4">{{ isEditMode ? 'Edit Post' : 'Create New Post' }}</h1>
      </v-col>
    </v-row>

    <v-alert v-if="blogPostsStore.error" type="error" class="mb-4">{{ blogPostsStore.error }}</v-alert>

    <v-progress-linear v-if="blogPostsStore.loading && isEditMode" indeterminate color="primary"></v-progress-linear>

    <v-card v-else class="pa-4">
      <v-form @submit.prevent="savePost">
        <v-text-field
          v-model="post.title"
          label="Title"
          required
          :rules="[v => !!v || 'Title is required']"
        ></v-text-field>

        <v-textarea
          v-model="post.content"
          label="Content"
          required
          :rules="[v => !!v || 'Content is required']"
        ></v-textarea>

        <v-select
          v-model="post.status"
          :items="['publish', 'draft', 'pending', 'private']"
          label="Status"
          required
        ></v-select>

        <v-text-field
          v-if="isEditMode"
          v-model.number="post.priority"
          label="Display Priority (0 or higher)"
          type="number"
          min="0"
          required
          :rules="[v => (v !== null && v >= 0) || 'Priority must be a non-negative number']"
        ></v-text-field>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="router.push({ name: 'posts-list' })">Cancel</v-btn>
          <v-btn color="primary" type="submit" :loading="blogPostsStore.loading">
            <v-icon left>mdi-content-save</v-icon>
            {{ isEditMode ? 'Update' : 'Create' }}
          </v-btn>
        </v-card-actions>
      </v-form>
    </v-card>
  </v-container>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useBlogPostsStore } from '@/stores/blogPosts';

const route = useRoute();
const router = useRouter();
const blogPostsStore = useBlogPostsStore();

const isEditMode = computed(() => !!route.params.id);

const post = ref({
  title: '',
  content: '',
  status: 'publish',
  priority: 0, // Default for new posts
});

onMounted(async () => {
  if (isEditMode.value) {
    await blogPostsStore.fetchPost(route.params.id);
    if (blogPostsStore.currentPost) {
      // Map relevant fields from WP response to our form
      post.value = {
        title: blogPostsStore.currentPost.title,
        content: blogPostsStore.currentPost.content,
        status: blogPostsStore.currentPost.status,
        priority: blogPostsStore.currentPost.priority || 0,
      };
    }
  }
});

const savePost = async () => {
  try {
    if (isEditMode.value) {
      await blogPostsStore.updatePost(route.params.id, post.value);
    } else {
      await blogPostsStore.createPost(post.value);
    }
    router.push({ name: 'posts-list' });
  } catch (error) {
    console.error('Failed to save post:', error);
    // Error message already set by store
  }
};
</script>