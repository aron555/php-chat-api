<?php
@include_once "./utils/httpException.php";
@include_once "./app/app.controller.php";

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    return 0;
}

# Disable errors, should be false in production
# error_reporting(false);

# Check if function loaded successfully, or throw error
if (!function_exists("httpException")) {
    echo json_encode([
        "message" => "Runtime error",
        "statusCode" => 500
    ]);
    exit;
}

# Safely load config
unset($dbConfig);
@include_once "./db/db.config.php";
if (empty($dbConfig)) {
    httpException("Config load error", 500)['end']();
}

# Setup app
$app = new AppController($dbConfig);

