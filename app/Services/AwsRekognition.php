<?php


namespace App\Services;


use Aws\Rekognition\RekognitionClient;
use Illuminate\Support\Str;
use _;
use App\Service\S3;
use Exception;

class AwsRekognition
{
    protected $client;
    protected $applicableLables = [
        'Nudity',
        'Partial Nudity',
        'Explicit Nudity',
        'Sexual Activity'
    ];

    /**
     * Create an instance of AWS Rekognition
     * 
     * @return void
     */
    public function __construct()
    {
        $this->client = new RekognitionClient([
            'region' => env('AWS_DEFAULT_REGION', 'us-east-2'),
            'version' => env('AWS_VERSION')
        ]);
    }

    /**
     * Moderate image
     * 
     * @param string $url S3 image URL
     * @return boolean Safe or not
     */
    public function isSafeImage($url)
    {
        $response = $this->client->detectModerationLabels([
            'Image' => [
                'S3Object' => [
                    'Bucket' => env('AWS_BUCKET'),
                    'Name' => S3::getFilepath($url)
                ],
            ],
            'MinConfidence' => 60
        ]);
        $moderationLabels = $response['ModerationLabels'];
        $applicableLables = $this->applicableLables;
        $nudity = _::filter(
            $moderationLabels,
            function($label) use ($applicableLables) {
                return in_array($label['Name'], $applicableLables);
            }
        );
        return !$nudity;
    }

    /**
     * Start video moderation
     * 
     * @param string $filepath S3 video URL
     * @return string Job id
     */
    public function video ($url)
    {
        $response = $this->client->startContentModeration([
            'ClientRequestToken' => Str::random(),
            'Video' => [
                'S3Object' => [
                    'Bucket' => env('AWS_BUCKET'),
                    'Name' => S3::getFilepath($url)
                ],
            ],
            'MinConfidence' => 60
        ]);
        return $response->get('JobId');
    }

    /**
     * Moderate video
     * 
     * @param string $jobId Job id 
     * @throws Exception If job status is not success
     * @return boolean Safe or not
     */
    public function isSafeVideo($jobId)
    {
        $moderation = $this->client->getContentModeration(['JobId' => $jobId]);
        $status = $moderation['JobStatus'];
        $isSuccess = $status === 'SUCCEEDED';
        if ($isSuccess) {
            $moderationLabels = $moderation['ModerationLabels'];
            $moderationLabels = _::map($moderationLabels, function($label) {
                return $label['ModerationLabel'];
            });
            $applicableLables = $this->applicableLables;
            $nudity = _::filter(
                $moderationLabels,
                function($label) use ($applicableLables) {
                    return in_array($label['Name'], $applicableLables);
                }
            );
            return !$nudity;
        } else {
            $message = 'Moderation is ' . $status;
            throw new Exception($message);
        }
    }
}
