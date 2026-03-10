<?php
/**
 * Database Connection using PDO
 * Configured for both local and cloud environments using Environment Variables.
 */

$host = getenv('DB_HOST') ?: 'byhrxwbsgw3qn1pix9ky-mysql.services.clever-cloud.com';
$db = getenv('DB_NAME') ?: 'byhrxwbsgw3qn1pix9ky';
$user = getenv('DB_USER') ?: 'utfeg78xjtoqdlac';
$pass = getenv('DB_PASS') ?: 'rmpr8nEU1yWB9UgJJxlp';
$port = getenv('DB_PORT') ?: '3306';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;port=$port;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int) $e->getCode());
}
