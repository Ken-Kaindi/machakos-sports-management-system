<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Handle form submissions
$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid security token. Please try again.";
    } else {
        
        // ADD USER
        if (isset($_POST['form_action']) && $_POST['form_action'] === 'add_user') {
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $full_name = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? 'staff';
            
            // Validate inputs
            if (empty($username) || empty($password) || empty($full_name) || empty($email)) {
                $error = "All fields are required!";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Please enter a valid email address!";
            } elseif (strlen($password) < 8) {
                $error = "Password must be at least 8 characters long!";
            } elseif (!preg_match('/[A-Z]/', $password)) {
                $error = "Password must contain at least one uppercase letter!";
            } elseif (!preg_match('/[a-z]/', $password)) {
                $error = "Password must contain at least one lowercase letter!";
            } elseif (!preg_match('/[0-9]/', $password)) {
                $error = "Password must contain at least one number!";
            } else {
                try {
                    // Check if username exists
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    
                    if ($stmt->rowCount() > 0) {
                        $error = "Username already exists!";
                    } else {
                        // Check if email exists
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                        $stmt->execute([$email]);
                        
                        if ($stmt->rowCount() > 0) {
                            $error = "Email already registered!";
                        } else {
                            // Hash password
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            
                            // Insert new user
                            $stmt = $pdo->prepare("
                                INSERT INTO users (username, password, full_name, email, role, created_at) 
                                VALUES (?, ?, ?, ?, ?, NOW())
                            ");
                            if ($stmt->execute([$username, $hashed_password, $full_name, $email, $role])) {
                                $success = "User added successfully!";
                                if (function_exists('logActivity')) {
                                    logActivity('user_added', "Added new user: $username ($role)");
                                }
                                $_POST = [];
                            } else {
                                $error = "Failed to add user. Please try again.";
                            }
                        }
                    }
                } catch (PDOException $e) {
                    error_log("Error adding user: " . $e->getMessage());
                    $error = "Database error occurred. Please try again.";
                }
            }
        }
        
        // DELETE USER
        if (isset($_POST['form_action']) && $_POST['form_action'] === 'delete_user') {
            $user_id = (int)$_POST['user_id'];
            
            try {
                // Check if trying to delete self
                if (isset($_SESSION['user_id']) && $user_id == $_SESSION['user_id']) {
                    $error = "You cannot delete your own account!";
                } else {
                    // Check if user has associated records
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM players WHERE registered_by = ?");
                    $stmt->execute([$user_id]);
                    if ($stmt->fetchColumn() > 0) {
                        $error = "Cannot delete user because they have registered players!";
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                        if ($stmt->execute([$user_id])) {
                            $success = "User deleted successfully!";
                            if (function_exists('logActivity')) {
                                logActivity('user_deleted', "Deleted user ID: $user_id");
                            }
                        } else {
                            $error = "Failed to delete user. Please try again.";
                        }
                    }
                }
            } catch (PDOException $e) {
                error_log("Error deleting user: " . $e->getMessage());
                $error = "Database error occurred. Please try again.";
            }
        }
    }
}

// Get all users
try {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY role, username");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching users: " . $e->getMessage());
    $users = [];
    $error = "Error loading user list. Please refresh the page.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Machakos University</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ============================================
           GLOBAL STYLES
           ============================================ */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; }
        
        .app-container { display: flex; min-height: 100vh; }
        
        /* ============================================
           SIDEBAR
           ============================================ */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
            color: white;
            min-height: 100vh;
            padding: 20px 0;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 100;
            box-shadow: 4px 0 30px rgba(0,0,0,0.15);
        }
        .sidebar-header { text-align: center; padding: 0 20px 25px; border-bottom: 1px solid rgba(255,255,255,0.06); }
        .sidebar-header i { font-size: 40px; color: #f39c12; }
        .sidebar-header h3 { margin: 10px 0 0; font-size: 18px; font-weight: 600; }
        .sidebar-header small { display: block; font-size: 11px; color: rgba(255,255,255,0.4); margin-top: 2px; }
        
        .sidebar-menu { padding: 15px 0; }
        .sidebar-menu ul { list-style: none; padding: 0; margin: 0; }
        .sidebar-menu li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 22px;
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            font-size: 14px;
        }
        .sidebar-menu li a:hover { background: rgba(255,255,255,0.05); color: white; }
        .sidebar-menu li.active a {
            background: rgba(255,255,255,0.08);
            color: white;
            border-left-color: #f39c12;
        }
        .sidebar-menu li a i { width: 20px; text-align: center; font-size: 16px; }
        .sidebar-menu li.divider { height: 1px; background: rgba(255,255,255,0.06); margin: 10px 20px; list-style: none; }
        .sidebar-footer { position: absolute; bottom: 20px; text-align: center; width: 100%; font-size: 11px; color: rgba(255,255,255,0.25); }
        
        /* ============================================
           MAIN CONTENT
           ============================================ */
        .main-content {
            flex: 1;
            padding: 25px 30px;
            margin-left: 260px;
            min-height: 100vh;
            background: #f0f2f5;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .page-header h1 {
            color: #1a5276;
            font-size: 26px;
            font-weight: 700;
        }
        .page-header h1 i { color: #f39c12; margin-right: 12px; }
        .page-header .user-badge {
            display: flex;
            align-items: center;
            gap: 12px;
            background: white;
            padding: 8px 20px 8px 16px;
            border-radius: 50px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }
        .page-header .user-badge i { font-size: 20px; color: #f39c12; }
        .page-header .user-badge span { font-weight: 500; color: #555; }
        
        /* ============================================
           ALERTS
           ============================================ */
        .alert {
            padding: 14px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.4s ease;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .alert-success {
            background: linear-gradient(135deg, #d4edda, #b7ebc5);
            color: #0b5e2e;
            border: 1px solid #b7ebc5;
        }
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert i { font-size: 20px; }
        
        /* ============================================
           CARDS
           ============================================ */
        .card {
            background: white;
            border-radius: 16px;
            padding: 28px 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.04);
            margin-bottom: 25px;
            border: 1px solid #f0f0f0;
            transition: box-shadow 0.3s ease;
        }
        .card:hover { box-shadow: 0 8px 30px rgba(0,0,0,0.06); }
        .card-title {
            color: #1a5276;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-title i { color: #f39c12; }
        
        /* ============================================
           FORM
           ============================================ */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }
        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block;
            font-weight: 500;
            color: #333;
            margin-bottom: 5px;
            font-size: 13px;
        }
        .form-group label i { margin-right: 6px; color: #f39c12; }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid #e9ecef;
            border-radius: 10px;
            font-size: 14px;
            background: #fafbfc;
            transition: all 0.3s ease;
            color: #333;
        }
        .form-group input:focus,
        .form-group select:focus {
            border-color: #f39c12;
            background: white;
            outline: none;
            box-shadow: 0 0 0 4px rgba(243,156,18,0.08);
        }
        .form-group small {
            display: block;
            color: #999;
            font-size: 11px;
            margin-top: 4px;
        }
        .form-group small i { margin-right: 4px; }
        
        .btn {
            padding: 11px 28px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #1a5276, #2980b9);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(26,82,118,0.25);
        }
        .btn-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(231,76,60,0.25);
        }
        .btn-sm { padding: 6px 14px; font-size: 12px; border-radius: 8px; }
        .btn i { font-size: 14px; }
        
        /* ============================================
           TABLE
           ============================================ */
        .table-wrapper { overflow-x: auto; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        th {
            background: linear-gradient(135deg, #f8f9fa, #eef2f7);
            color: #333;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        tr:hover td { background: #f8fafc; }
        .text-center { text-align: center; }
        
        /* ============================================
           BADGES
           ============================================ */
        .badge {
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .badge-success {
            background: linear-gradient(135deg, #d4edda, #a3dfb5);
            color: #0b5e2e;
        }
        .badge-info {
            background: linear-gradient(135deg, #d1ecf1, #8fd3dd);
            color: #0c5460;
        }
        .badge-secondary {
            background: #e9ecef;
            color: #6c757d;
        }
        
        /* ============================================
           EMPTY STATE
           ============================================ */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
        .empty-state i { font-size: 48px; color: #ddd; margin-bottom: 15px; display: block; }
        
        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s ease; width: 280px; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 15px; }
            .form-row { grid-template-columns: 1fr; }
            .page-header { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-trophy"></i>
                <h3>Sports Management</h3>
                <small>Machakos University</small>
            </div>
            <nav class="sidebar-menu">
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="players.php"><i class="fas fa-users"></i> Players</a></li>
                    <li><a href="clearance.php"><i class="fas fa-clipboard-check"></i> Clearance</a></li>
                    <li><a href="issue_item.php"><i class="fas fa-plus-circle"></i> Issue New Item</a></li>
                    <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                    <li class="active"><a href="manage_users.php"><i class="fas fa-user-cog"></i> Manage Users</a></li>
                    <li class="divider"></li>
                    <li><a href="../about.php"><i class="fas fa-info-circle"></i> About Us</a></li>
                    <li><a href="../contact.php"><i class="fas fa-envelope"></i> Contact Us</a></li>
                    <li class="divider"></li>
                    <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">v1.0.0 &copy; <?php echo date('Y'); ?></div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="fas fa-user-cog"></i> Manage Users</h1>
                <div class="user-badge">
                    <i class="fas fa-user-circle"></i>
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></span>
                </div>
            </div>

            <!-- Alerts -->
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Add User Form -->
            <div class="card">
                <div class="card-title"><i class="fas fa-user-plus"></i> Add New User</div>
                <form action="manage_users.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="form_action" value="add_user">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username"><i class="fas fa-user"></i> Username</label>
                            <input type="text" id="username" name="username" required 
                                   minlength="3" maxlength="50" 
                                   placeholder="Choose a username"
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="password"><i class="fas fa-lock"></i> Password</label>
                            <input type="text" id="password" name="password" required 
                                   minlength="8" 
                                   placeholder="Create a strong password"
                                   value="<?php echo htmlspecialchars($_POST['password'] ?? ''); ?>">
                            <small><i class="fas fa-info-circle"></i> Minimum 8 characters with uppercase, lowercase, and numbers</small>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name"><i class="fas fa-id-card"></i> Full Name</label>
                            <input type="text" id="full_name" name="full_name" required 
                                   maxlength="100"
                                   placeholder="Enter full name"
                                   value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" id="email" name="email" required 
                                   maxlength="100"
                                   placeholder="user@machakos.ac.ke"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row" style="margin-bottom: 10px;">
                        <div class="form-group">
                            <label for="role"><i class="fas fa-user-tag"></i> Role</label>
                            <select id="role" name="role" required>
                                <option value="admin" <?php echo (($_POST['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Administrator</option>
                                <option value="staff" <?php echo (($_POST['role'] ?? '') === 'staff') ? 'selected' : ''; ?>>Staff</option>
                            </select>
                        </div>
                        <div class="form-group" style="display: flex; align-items: flex-end; padding-bottom: 4px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add User
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Users List -->
            <div class="card">
                <div class="card-title"><i class="fas fa-users"></i> System Users</div>
                
                <?php if (!empty($users)): ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge <?php echo ($user['role'] ?? 'staff') === 'admin' ? 'badge-success' : 'badge-info'; ?>">
                                            <?php echo ucfirst($user['role'] ?? 'Staff'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo isset($user['created_at']) ? date('Y-m-d', strtotime($user['created_at'])) : 'N/A'; ?></td>
                                    <td>
                                        <?php if (($user['id'] ?? 0) != ($_SESSION['user_id'] ?? 0)): ?>
                                            <form action="manage_users.php" method="POST" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="form_action" value="delete_user">
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete user <?php echo htmlspecialchars($user['username']); ?>?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge badge-secondary"><i class="fas fa-user-check"></i> Current User</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No users found.</p>
                        <small style="color: #bbb;">Add your first user using the form above.</small>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>