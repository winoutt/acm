<?php

namespace App\Http\Controllers;

use App\Jobs\ModeratePostAlbumJob;
use App\Jobs\ModeratePostImageJob;
use App\Jobs\ModeratePostVideoJob;
use App\Jobs\ModerateUserAvatarJob;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Services\AwsRekognition;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Validator;

class ModerateController extends Controller
{
    private $userRepo;
    private $postRepo;
    private $awsRekognition;

    function __construct()
    {
        $this->userRepo = new UserRepository;
        $this->postRepo = new PostRepository;
        $this->awsRekognition = new AwsRekognition;
    }

    /**
     * Moderate data
     * 
     * Accept User and Post. And moderate the image 
     * and video contents using queued jobs. And unsafe 
     * data will be removed.
     * 
     * @return Response
     */
    public function moderate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:user,post',
            'id' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->first(), 422);
        }
        $isUser = $request->type === 'user';
        $isPost = $request->type === 'post';
        try {
            if ($isUser) {
                $user = $this->userRepo->get($request->id);
                dispatch(new ModerateUserAvatarJob($user));
            } else if ($isPost) {
                $post = $this->postRepo->get($request->id);
                if ($post->content) {
                    $isImagePost = $post->content->type === 'image';
                    $isVideoPost = $post->content->type === 'video';
                    if ($isImagePost) {
                        dispatch(new ModeratePostImageJob($post));
                    } else if ($isVideoPost) {
                        $jobId = $this->awsRekognition->video($post->content->body);
                        $delay = Carbon::now()->addSeconds(30);
                        Queue::later($delay, new ModeratePostVideoJob($post, $jobId));
                    } else {
                        return response()->json('Invalid post type', 400);
                    }
                } else if ($post->album) {
                    dispatch(new ModeratePostAlbumJob($post));
                } else {
                    return response()->json('Invalid post type', 400);
                }
            } else {
                return response()->json('Invalid type', 400);
            }
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 503);
        }
    }
}
