<?php
/**
 * Authentication System
 * Machakos University Sports Management System
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

// ============================================
// AUTHENTICATION FUNCTIONS
// ============================================

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header("Location: ../index.php?error=access_denied");
        exit();
    }
}

function requireStaff() {
    requireLogin();
    if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'staff'])) {
        header("Location: ../index.php?error=access_denied");
        exit();
    }
}

function login($username, $password) {
    global $pdo;
    $username = trim($username);
    $password = trim($password);
    if (empty($username) || empty($password)) return false;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'] ?? 'staff';
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'] ?? '';
            $_SESSION['login_time'] = time();
            return true;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

function logout() {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

function getUserById($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT id, username, full_name, email, role, created_at, last_login, is_active 
            FROM users WHERE id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get user error: " . $e->getMessage());
        return false;
    }
}

function updatePassword($user_id, $new_password) {
    global $pdo;
    try {
        if (strlen($new_password) < 8) return false;
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$hashed, $user_id]);
    } catch (PDOException $e) {
        error_log("Password update error: " . $e->getMessage());
        return false;
    }
}

function hasPermission($required_role) {
    if (!isLoggedIn()) return false;
    $role = $_SESSION['user_role'] ?? 'staff';
    if ($required_role === 'admin') return $role === 'admin';
    return in_array($role, ['admin', 'staff']);
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ============================================
// logActivity() is NOW REMOVED from here
// It exists in functions.php
// ============================================

$csrf_token = generateCSRFToken();
?>