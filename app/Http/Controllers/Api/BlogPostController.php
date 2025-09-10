<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\WordPressService;
use App\Models\BlogPostPriority; // Our local model for priorities
use Illuminate\Support\Facades\Log;

class BlogPostController extends Controller
{
    protected $wordpressService;

    public function __construct(WordPressService $wordpressService)
    {
        $this->wordpressService = $wordpressService;
    }

    /**
     * Display a listing of the blog posts, combined with local priorities.
     */
    public function index(Request $request)
    {
        $wordpressPosts = $this->wordpressService->getPosts();

        if (is_null($wordpressPosts)) {
            return response()->json(['message' => 'Could not fetch posts from WordPress.'], 500);
        }

        $priorities = BlogPostPriority::all()->keyBy('wordpress_post_id');

        $posts = collect($wordpressPosts)->map(function ($post) use ($priorities) {
            $postId = (string) $post['ID']; // Ensure consistent type
            $priority = $priorities->get($postId);
            $post['priority'] = $priority ? $priority->priority : 0; // Default priority 0
            // Add a simplified content field, extracting text from HTML
            $post['excerpt_content'] = strip_tags($post['content']);
            $post['excerpt_content'] = mb_substr($post['excerpt_content'], 0, 150) . (mb_strlen($post['excerpt_content']) > 150 ? '...' : '');

            return $post;
        });

        // Apply sorting by priority if requested
        if ($request->boolean('sort_by_priority')) { // Use boolean to check for true/false
            $posts = $posts->sortByDesc('priority')->values();
        }

        return response()->json($posts);
    }

    /**
     * Store a newly created blog post in WordPress.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'status' => 'sometimes|string|in:publish,draft,pending,private', // Add status option
        ]);

        $wordpressPost = $this->wordpressService->createPost([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'status' => $validated['status'] ?? 'publish', // Default to publish
        ]);

        if (is_null($wordpressPost)) {
            return response()->json(['message' => 'Failed to create post in WordPress.'], 500);
        }

        // Add default priority entry
        BlogPostPriority::create([
            'wordpress_post_id' => (string) $wordpressPost['ID'],
            'priority' => 0,
        ]);

        return response()->json($wordpressPost, 201);
    }

    /**
     * Display the specified blog post.
     */
    public function show(string $id)
    {
        $wordpressPost = $this->wordpressService->getPost($id);

        if (is_null($wordpressPost)) {
            return response()->json(['message' => 'Post not found in WordPress.'], 404);
        }

        $priority = BlogPostPriority::where('wordpress_post_id', $id)->first();
        $wordpressPost['priority'] = $priority ? $priority->priority : 0;

        return response()->json($wordpressPost);
    }

    /**
     * Update the specified blog post in WordPress.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'status' => 'sometimes|string|in:publish,draft,pending,private',
            'priority' => 'sometimes|integer|min:0', // Priority update
        ]);

        // Prepare data for WordPress update
        $wpData = collect($validated)->only(['title', 'content', 'status'])->toArray();

        // Only send to WP if there's actual WP data to update
        if (!empty($wpData)) {
            $wordpressPost = $this->wordpressService->updatePost($id, $wpData);

            if (is_null($wordpressPost)) {
                return response()->json(['message' => 'Failed to update post in WordPress.'], 500);
            }
        } else {
            // If only priority is updated, we don't call WordPress API
            $wordpressPost = $this->wordpressService->getPost($id); // Just fetch current if no WP update needed
            if (is_null($wordpressPost)) {
                return response()->json(['message' => 'Post not found in WordPress to update priority.'], 404);
            }
        }


        // Handle local priority update if present
        if (isset($validated['priority'])) {
            BlogPostPriority::updateOrCreate(
                ['wordpress_post_id' => $id],
                ['priority' => $validated['priority']]
            );
        }

        // Fetch updated post with priority for consistent response
        $updatedWordPressPost = $this->wordpressService->getPost($id);
        if (is_null($updatedWordPressPost)) {
            return response()->json(['message' => 'Post updated, but could not refetch from WordPress.'], 500);
        }

        $priority = BlogPostPriority::where('wordpress_post_id', $id)->first();
        $updatedWordPressPost['priority'] = $priority ? $priority->priority : 0;

        return response()->json($updatedWordPressPost);
    }

    /**
     * Remove the specified blog post from WordPress.
     */
    public function destroy(string $id)
    {
        $deleted = $this->wordpressService->deletePost($id);

        if (!$deleted) {
            return response()->json(['message' => 'Failed to delete post from WordPress.'], 500);
        }

        // Also delete local priority entry
        BlogPostPriority::where('wordpress_post_id', $id)->delete();

        return response()->json(['message' => 'Post deleted successfully.'], 204);
    }

    /**
     * Set display priority for a blog post.
     */
    public function setPriority(Request $request, string $id)
    {
        $validated = $request->validate([
            'priority' => 'required|integer|min:0',
        ]);

        $post = BlogPostPriority::updateOrCreate(
            ['wordpress_post_id' => $id],
            ['priority' => $validated['priority']]
        );

        return response()->json($post);
    }
}