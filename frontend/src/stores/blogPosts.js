import { defineStore } from 'pinia'
import axios from 'axios'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

axios.defaults.baseURL = API_BASE_URL

export const useBlogPostsStore = defineStore('blogPosts', {
  state: () => ({
    posts: [],
    currentPost: null,
    loading: false,
    error: null,
  }),
  actions: {
    async fetchPosts(sortByPriority = false) {
      this.loading = true
      this.error = null
      try {
        const params = sortByPriority ? { sort_by_priority: true } : {};
        const response = await axios.get('/blog-posts', { params });
        this.posts = response.data;
      } catch (error) {
        console.error('Error fetching blog posts:', error)
        this.error = 'Failed to fetch blog posts.'
      } finally {
        this.loading = false
      }
    },
    async fetchPost(id) {
      this.loading = true
      this.error = null
      try {
        const response = await axios.get(`/blog-posts/${id}`)
        this.currentPost = response.data
      } catch (error) {
        console.error(`Error fetching post ${id}:`, error)
        this.error = `Failed to fetch post ${id}.`
      } finally {
        this.loading = false
      }
    },
    async createPost(postData) {
      this.loading = true
      this.error = null
      try {
        const response = await axios.post('/blog-posts', postData)
        this.posts.push(response.data) // Add to local state
        return response.data
      } catch (error) {
        console.error('Error creating post:', error.response?.data || error)
        this.error = error.response?.data?.message || 'Failed to create post.'
        throw error // Re-throw to handle in component
      } finally {
        this.loading = false
      }
    },
    async updatePost(id, postData) {
      this.loading = true
      this.error = null
      try {
        const response = await axios.put(`/blog-posts/${id}`, postData)
        // Find and update the post in the local state
        const index = this.posts.findIndex(p => p.ID == id)
        if (index !== -1) {
          this.posts[index] = { ...this.posts[index], ...response.data }
        }
        return response.data
      } catch (error) {
        console.error(`Error updating post ${id}:`, error.response?.data || error)
        this.error = error.response?.data?.message || `Failed to update post ${id}.`
        throw error // Re-throw to handle in component
      } finally {
        this.loading = false
      }
    },
    async deletePost(id) {
      this.loading = true
      this.error = null
      try {
        await axios.delete(`/blog-posts/${id}`)
        this.posts = this.posts.filter(post => post.ID != id) // Remove from local state
        return true
      } catch (error) {
        console.error(`Error deleting post ${id}:`, error.response?.data || error)
        this.error = error.response?.data?.message || `Failed to delete post ${id}.`
        throw error // Re-throw to handle in component
      } finally {
        this.loading = false
      }
    },
    async setPostPriority(id, priority) {
        this.loading = true;
        this.error = null;
        try {
            const response = await axios.post(`/blog-posts/${id}/set-priority`, { priority });
            // Update the post's priority in the local state
            const index = this.posts.findIndex(p => p.ID == id);
            if (index !== -1) {
                this.posts[index].priority = response.data.priority;
            }
            return response.data;
        } catch (error) {
            console.error(`Error setting priority for post ${id}:`, error.response?.data || error);
            this.error = error.response?.data?.message || `Failed to set priority for post ${id}.`;
            throw error;
        } finally {
            this.loading = false;
        }
    }
  }
})