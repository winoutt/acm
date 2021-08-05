<?php

namespace App\Service;

use App\Post;
use Pusher\Pusher;

class Broadcast
{
    private $pusher;

    /**
     * Create pusher instance
     * 
     * @return void
     */
    function __construct()
    {
        $this->pusher = new Pusher(
            env('PUSHER_APP_KEY'),
            env('PUSHER_APP_SECRET'),
            env('PUSHER_APP_ID'),
            ['cluster' => env('PUSHER_APP_CLUSTER'), 'useTLS' => true]
        );
    }

    /**
     * Post deleted broadcast
     * 
     * @param Post $post Post instance
     * @return void
     */
    public function postDeleted(Post $post)
    {
        $payload = ['post_id' => $post->id];
        $this->pusher->trigger('post', 'post.deleted', $payload);
    }
}