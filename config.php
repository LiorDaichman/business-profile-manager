<?php
// Load environment variables from .env file
require_once 'vendor/autoload.php';

// Initialize Dotenv to load .env
Dotenv\Dotenv::createImmutable(__DIR__)->load();

$dbHost = $_ENV['DB_HOST'];
$dbName = $_ENV['DB_NAME'];
$dbUser = $_ENV['DB_USER'];
$dbPassword = $_ENV['DB_PASSWORD'];

$awsAccessKey = $_ENV['AWS_ACCESS_KEY_ID'];
$awsSecretKey = $_ENV['AWS_SECRET_ACCESS_KEY'];
$awsBucketName = $_ENV['AWS_BUCKET_NAME'];
$awsRegion = $_ENV['AWS_REGION'];