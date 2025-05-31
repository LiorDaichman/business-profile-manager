<?php
require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Dotenv\Dotenv;

// Load environment variables from the .env file
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialize the S3 client with credentials from environment variables
function getS3Client() {
    // Load credentials from environment variables
    $awsAccessKey = getenv('AWS_ACCESS_KEY_ID');
    $awsSecretKey = getenv('AWS_SECRET_ACCESS_KEY');
    $awsRegion = getenv('AWS_REGION');
    
    if (!$awsAccessKey || !$awsSecretKey || !$awsRegion) {
        throw new Exception('Missing AWS credentials in environment variables.');
    }

    return new S3Client([
        'version' => 'latest',
        'region'  => $awsRegion,
        'credentials' => [
            'key'    => $awsAccessKey,
            'secret' => $awsSecretKey,
        ],
        'http' => [
            'verify' => false // Disable SSL verification temporarily
        ]
    ]);
}

// Upload file to S3
function uploadFileToS3($file, $business_id) {
    // Get the bucket name from environment variable
    $awsBucketName = getenv('AWS_BUCKET_NAME');
    
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Create a unique filename to prevent overwriting
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $uniqueFilename = 'business_' . $business_id . '_header_' . time() . '.' . $fileExtension;
    
    try {
        $s3Client = getS3Client();
        
        // Upload the file - REMOVED the ACL parameter
        $result = $s3Client->putObject([
            'Bucket' => $awsBucketName,
            'Key'    => 'header_images/' . $uniqueFilename,
            'SourceFile' => $file['tmp_name'],
            'ContentType' => $file['type'],
            // Removed 'ACL' => 'public-read' as the bucket doesn't support ACLs
        ]);
        
        // Return the filename if successful
        return $uniqueFilename;
    } catch (AwsException $e) {
        error_log('S3 Upload Error: ' . $e->getMessage());
        return false;
    }
}

// Get the S3 URL for an image
function getS3ImageUrl($filename) {
    // Get the bucket name and region from environment variables
    $awsBucketName = getenv('AWS_BUCKET_NAME');
    $awsRegion = getenv('AWS_REGION');
    
    if (empty($filename)) {
        return '';
    }
    
    return "https://{$awsBucketName}.s3.{$awsRegion}.amazonaws.com/header_images/{$filename}";
}
