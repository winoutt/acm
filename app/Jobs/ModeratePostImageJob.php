<?php

namespace App\Jobs;

use App\Post;
use App\Repository\PostRepository;
use App\Service\Broadcast;
use App\Service\S3;
use App\Services\AwsRekognition;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;

class ModeratePostImageJob extends Job implements ShouldQueue
{
    public $tries = 3;
    public $retryAfter = 30;

    private $post;

    /**
     * Create a new post image moderation job instance.
     * 
     * @param Post $post Post instance
     * @return void
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    /**
     * Execute the post image moderation.
     * 
     * @throws Exception If job failed. To retry.
     * @return void
     */
    public function handle()
    {
        try {
            $awsRekognition = new AwsRekognition;
            $isSafe = $awsRekognition->isSafeImage($this->post->content->body);
            if ($isSafe) return;
            $unsafeImages = [
                $this->post->content->body,
                $this->post->content->photo_original,
            ];
            $postRepo = new PostRepository;
            $postRepo->delete($this->post);
            $broadcast = new Broadcast();
            $broadcast->postDeleted($this->post);
            S3::deleteFiles($unsafeImages);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
