<?php

namespace App\Jobs;

use App\Repository\UserRepository;
use App\Service\S3;
use App\Services\AwsRekognition;
use App\User;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;

class ModerateUserAvatarJob extends Job implements ShouldQueue
{
    public $tries = 3;
    public $retryAfter = 30;

    private $user;

    /**
     * Create a new user avatar moderation job instance.
     *
     * @param User $user User instance
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the user avatar moderation.
     *
     * @throws Exception If job failed. To retry.
     * @return void
     */
    public function handle()
    {
        try {
            $awsRekognition = new AwsRekognition;
            $isSafe = $awsRekognition->isSafeImage($this->user->avatar);
            if ($isSafe) return;
            $unsafeAvatars = [
                $this->user->avatar,
                $this->user->avatar_original,
            ];
            $userRepo = new UserRepository;
            $userRepo->resetAvatar($this->user);
            S3::deleteFiles($unsafeAvatars);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
