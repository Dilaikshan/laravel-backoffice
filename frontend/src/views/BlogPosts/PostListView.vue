<template>
  <v-container>
    <v-row class="mb-4">
      <v-col>
        <h1 class="text-h4">Blog Posts</h1>
      </v-col>
      <v-col class="d-flex justify-end align-center">
        <v-switch
          v-model="sortByPriority"
          label="Sort by Priority"
          hide-details
          class="mr-4"
          color="primary"
        ></v-switch>
        <v-btn color="primary" @click="router.push({ name: 'post-create' })">
          <v-icon left>mdi-plus</v-icon> New Post
        </v-btn>
      </v-col>
    </v-row>

    <v-alert v-if="blogPostsStore.error" type="error" class="mb-4">{{ blogPostsStore.error }}</v-alert>

    <v-progress-linear v-if="blogPostsStore.loading" indeterminate color="primary"></v-progress-linear>

    <v-data-table
      v-else
      :headers="headers"
      :items="blogPostsStore.posts"
      item-key="ID"
      class="elevation-1"
      :items-per-page="10"
    >
      <template v-slot:item.title="{ item }">
        <router-link :to="{ name: 'post-edit', params: { id: item.ID } }" class="text-decoration-none">
          {{ item.title }}
        </router-link>
      </template>
      <template v-slot:item.content="{ item }">
        <div v-html="item.excerpt_content"></div>
      </template>
      <template v-slot:item.status="{ item }">
        <v-chip :color="getStatusColor(item.status)" small>{{ item.status }}</v-chip>
      </template>
      <template v-slot:item.priority="{ item }">
        <v-chip color="info" small>{{ item.priority }}</v-chip>
      </template>
      <template v-slot:item.actions="{ item }">
        <v-btn icon small class="mr-2" @click="router.push({ name: 'post-edit', params: { id: item.ID } })">
          <v-icon>mdi-pencil</v-icon>
        </v-btn>
        <v-btn icon small color="error" @click="confirmDelete(item)">
          <v-icon>mdi-delete</v-icon>
        </v-btn>
        <v-btn icon small class="ml-2" @click="editPriority(item)">
          <v-icon>mdi-priority-high</v-icon>
        </v-btn>
      </template>
    </v-data-table>

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="deleteDialog" max-width="500px">
      <v-card>
        <v-card-title class="headline">Confirm Deletion</v-card-title>
        <v-card-text>
          Are you sure you want to delete the post "{{ selectedPost?.title }}"? This action cannot be undone.
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="blue darken-1" text @click="deleteDialog = false">Cancel</v-btn>
          <v-btn color="red darken-1" text @click="deletePost">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Edit Priority Dialog -->
    <v-dialog v-model="priorityDialog" max-width="500px">
      <v-card>
        <v-card-title class="headline">Set Priority for "{{ selectedPost?.title }}"</v-card-title>
        <v-card-text>
          <v-text-field
            v-model.number="tempPriority"
            label="Priority Value (0 or higher)"
            type="number"
            min="0"
            required
          ></v-text-field>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="blue darken-1" text @click="priorityDialog = false">Cancel</v-btn>
          <v-btn color="primary" text @click="setPriority">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useBlogPostsStore } from '@/stores/blogPosts';

const router = useRouter();
const blogPostsStore = useBlogPostsStore();

const headers = [
  { title: 'Title', key: 'title' },
  { title: 'Content', key: 'excerpt_content' },
  { title: 'Status', key: 'status', width: '120px' },
  { title: 'Priority', key: 'priority', width: '100px' },
  { title: 'Actions', key: 'actions', sortable: false, width: '180px' },
];

const sortByPriority = ref(false);

const deleteDialog = ref(false);
const priorityDialog = ref(false);
const selectedPost = ref(null);
const tempPriority = ref(0);

const getStatusColor = (status) => {
  switch (status) {
    case 'publish': return 'green';
    case 'draft': return 'orange';
    case 'pending': return 'blue';
    case 'private': return 'purple';
    default: return 'grey';
  }
};

const confirmDelete = (post) => {
  selectedPost.value = post;
  deleteDialog.value = true;
};

const deletePost = async () => {
  if (selectedPost.value) {
    try {
      await blogPostsStore.deletePost(selectedPost.value.ID);
      deleteDialog.value = false;
      selectedPost.value = null;
    } catch (error) {
      // Error handled by store, maybe show a snackbar here
      console.error('Failed to delete post:', error);
    }
  }
};

const editPriority = (post) => {
  selectedPost.value = post;
  tempPriority.value = post.priority;
  priorityDialog.value = true;
};

const setPriority = async () => {
  if (selectedPost.value) {
    try {
      await blogPostsStore.setPostPriority(selectedPost.value.ID, tempPriority.value);
      priorityDialog.value = false;
      selectedPost.value = null;
      // Re-fetch to ensure sorting is updated if active
      await blogPostsStore.fetchPosts(sortByPriority.value);
    } catch (error) {
      console.error('Failed to set priority:', error);
    }
  }
};

onMounted(() => {
  blogPostsStore.fetchPosts(sortByPriority.value);
});

watch(sortByPriority, (newVal) => {
  blogPostsStore.fetchPosts(newVal);
});
</script>