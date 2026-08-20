<?php
/**
 * ============================================================
 * Configuration Example
 * Machakos University Sports Management System V2.0
 * ============================================================
 *
 * Rename/copy this file to:
 *
 *     config.php
 *
 * Then update the database and application settings.
 */

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Error Reporting
|--------------------------------------------------------------------------
*/

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

/*
|--------------------------------------------------------------------------
| Timezone
|--------------------------------------------------------------------------
*/

date_default_timezone_set('Africa/Nairobi');

/*
|--------------------------------------------------------------------------
| Database Configuration
|--------------------------------------------------------------------------
*/

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');

define('DB_USER', 'YOUR_DATABASE_USER');
define('DB_PASS', 'YOUR_DATABASE_PASSWORD');

define('DB_NAME', 'machakos_sports_v2');

/*
|--------------------------------------------------------------------------
| Application Configuration
|--------------------------------------------------------------------------
*/

define(
    'APP_NAME',
    'Machakos University Sports Management System'
);

define('APP_VERSION', '2.0.0');

define(
    'APP_URL',
    'http://localhost/machakos_sports_v2'
);

/*
|--------------------------------------------------------------------------
| Session Configuration
|--------------------------------------------------------------------------
*/

define('SESSION_TIMEOUT', 1800);

/*
|--------------------------------------------------------------------------
| Start Session
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

/*
|--------------------------------------------------------------------------
| Database Connection
|--------------------------------------------------------------------------
*/

try {

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        DB_HOST,
        DB_PORT,
        DB_NAME
    );

    $pdo = new PDO(
        $dsn,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ]
    );

} catch (PDOException $e) {

    error_log(
        'Database connection error: ' . $e->getMessage()
    );

    die(
        'Database connection failed. Please check the system configuration.'
    );
}

/*
|--------------------------------------------------------------------------
| Application Functions
|--------------------------------------------------------------------------
*/

$functionsFile = __DIR__ . '/functions.php';

if (file_exists($functionsFile)) {
    require_once $functionsFile;
}

/*
|--------------------------------------------------------------------------
| CSRF Protection
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] = bin2hex(
        random_bytes(32)
    );
}

$csrf_token = $_SESSION['csrf_token'];

/*
|--------------------------------------------------------------------------
| Session Timeout Protection
|--------------------------------------------------------------------------
*/

if (
    isset($_SESSION['last_activity']) &&
    (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT
) {

    unset(
        $_SESSION['user_id'],
        $_SESSION['username'],
        $_SESSION['role_id'],
        $_SESSION['role_name'],
        $_SESSION['full_name'],
        $_SESSION['last_activity']
    );
}

$_SESSION['last_activity'] = time();

/*
|--------------------------------------------------------------------------
| End Configuration
|--------------------------------------------------------------------------
*/