<?php

namespace App\Service;

use Illuminate\Support\Facades\Storage;

class S3
{
    /**
     * Get filepath from S3 URL
     * 
     * @param string $url S3 file URL
     * @return string Filepath
     */
    public static function getFilepath ($url)
    {
        $bucket = env('AWS_BUCKET');
        $region = env('AWS_DEFAULT_REGION');
        $s3Url = 'https://' . $bucket . '.s3.' . $region . '.amazonaws.com/';
        return str_replace($s3Url, '', $url);
    }
    /**
     * Delete file from S3 storage from URL
     * 
     * @param string $url S3 file URL
     * @return void
     */
    public static function deleteFile ($url)
    {
        $filepath = self::getFilepath($url);
        Storage::disk('s3')->delete($filepath);
    }

    /**
     * Delete files from S3 storage from URLs
     * 
     * @param array $urls S3 file URLs
     * @return void
     */
    public static function deleteFiles ($urls)
    {
        foreach($urls as $url) {
            self::deleteFile($url);
        }
    }
}