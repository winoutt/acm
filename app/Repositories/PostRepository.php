<?php

namespace App\Repository;

use App\Post;
use Exception;

class PostRepository
{
    /**
     * Get post
     * 
     * @param integer $postId Post id
     * @return Post Post instance
     */
    public function get ($postId)
    {
        $post = Post::find($postId);
        if (!$post) throw new Exception('Post not found');
        return $post;
    }

    /**
     * Check post existance
     * 
     * @param integer $postId Post id
     * @return boolean Exists or not
     */
    public function isExists ($postId)
    {
        try {
            $this->get($postId);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Delete post
     * 
     * @param Post $post Post instance
     * @return void
     */
    public function delete (Post $post)
    {
        $post->delete();
    }
}