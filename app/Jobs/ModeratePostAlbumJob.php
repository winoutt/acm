<?php

namespace App\Jobs;

use App\Post;
use App\Repository\PostRepository;
use App\Service\Broadcast;
use App\Service\S3;
use App\Services\AwsRekognition;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;

class ModeratePostAlbumJob extends Job implements ShouldQueue
{
    public $tries = 3;
    public $retryAfter = 30;

    private $post;

    /**
     * Create a new post album moderation job instance.
     * 
     * @param Post $post Post instance
     * @return void
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    /**
     * Execute the post album moderation.
     * 
     * @throws Exception If job failed. To retry.
     * @return void
     */
    public function handle()
    {
        try {
            $postRepo = new PostRepository;
            $broadcast = new Broadcast;
            $awsRekognition = new AwsRekognition;
            foreach($this->post->album as $albumPhoto) {
                $isSafe = $awsRekognition->isSafeImage($albumPhoto->photo);
                if ($isSafe) continue;
                $unsafePhotos = [
                    $albumPhoto->photo,
                    $albumPhoto->photo_original,
                ];
                if ($postRepo->isExists($this->post->id)) {
                    $postRepo->delete($this->post);
                    $broadcast->postDeleted($this->post);
                }
                S3::deleteFiles($unsafePhotos);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
