<?php

namespace App\Jobs;

use App\Post;
use App\Repository\PostRepository;
use App\Service\Broadcast;
use App\Service\S3;
use App\Services\AwsRekognition;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;

class ModeratePostVideoJob extends Job implements ShouldQueue
{
    public $tries = 3;
    public $retryAfter = 120;

    private $post;
    private $jobId;

    /**
     * Create a new post video moderation job instance.
     *
     * @param Post $post Post instance
     * @param string $jobId AWS Rekognition job id
     * @return void
     */
    public function __construct(Post $post, $jobId)
    {
        $this->post = $post;
        $this->jobId = $jobId;
    }

    /**
     * Execute the post video moderation.
     *
     * @throws Exception If job failed. To retry.
     * @return void
     */
    public function handle()
    {
        try {
            $awsRekognition = new AwsRekognition;
            $isSafe = $awsRekognition->isSafeVideo($this->jobId);
            if ($isSafe) return;
            $unsafeVideo = $this->post->content->body;
            $postRepo = new PostRepository;
            $postRepo->delete($this->post);
            $broadcast = new Broadcast();
            $broadcast->postDeleted($this->post);
            S3::deleteFile($unsafeVideo);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
