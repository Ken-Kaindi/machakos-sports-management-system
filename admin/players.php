<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

$success = null;
$error = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['form_action']) && $_POST['form_action'] === 'register') {
        
        $data = [
            'student_id' => trim($_POST['student_id'] ?? ''),
            'full_name' => trim($_POST['full_name'] ?? ''),
            'gender' => trim($_POST['gender'] ?? ''),
            'date_of_birth' => trim($_POST['date_of_birth'] ?? ''),
            'faculty' => trim($_POST['faculty'] ?? ''),
            'course' => trim($_POST['course'] ?? ''),
            'year_of_study' => (int)($_POST['year_of_study'] ?? 0),
            'team' => trim($_POST['team'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? '')
        ];
        
        $result = registerPlayer($data);
        
        if ($result === true) {
            $success = "Player registered successfully!";
            $_POST = [];
        } else {
            $error = $result;
        }
    }
}

$players = getAllPlayers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Players Management - Machakos University</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .app-container { display: flex; min-height: 100vh; }
        .main-content { flex: 1; padding: 25px 30px; margin-left: 260px; background: #f0f2f5; }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-weight: 500; color: #333; margin-bottom: 5px; font-size: 13px; }
        .form-group input, .form-group select { width: 100%; padding: 10px 14px; border: 1.5px solid #e9ecef; border-radius: 10px; font-size: 14px; background: #fafbfc; }
        .form-group input:focus, .form-group select:focus { border-color: #f39c12; outline: none; box-shadow: 0 0 0 4px rgba(243,156,18,0.08); }
        .form-group small { display: block; color: #999; font-size: 11px; margin-top: 4px; }
        .btn { padding: 11px 28px; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(135deg, #1a5276, #2980b9); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(26,82,118,0.25); }
        .btn-info { background: linear-gradient(135deg, #2980b9, #3498db); color: white; }
        .btn-info:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(41,128,185,0.25); }
        .btn-sm { padding: 6px 14px; font-size: 12px; border-radius: 8px; }
        .card { background: white; border-radius: 16px; padding: 28px 30px; box-shadow: 0 2px 15px rgba(0,0,0,0.04); margin-bottom: 25px; border: 1px solid #f0f0f0; }
        .card h2 { color: #1a5276; font-size: 18px; font-weight: 600; margin-bottom: 20px; }
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #f0f0f0; }
        th { background: linear-gradient(135deg, #f8f9fa, #eef2f7); color: #333; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.3px; }
        tr:hover td { background: #f8fafc; }
        .badge { padding: 4px 14px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
        .badge-success { background: linear-gradient(135deg, #d4edda, #a3dfb5); color: #0b5e2e; }
        .badge-warning { background: linear-gradient(135deg, #fff3cd, #ffe69b); color: #856404; }
        .alert { padding: 14px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; }
        .alert-success { background: linear-gradient(135deg, #d4edda, #b7ebc5); color: #0b5e2e; border: 1px solid #b7ebc5; }
        .alert-danger { background: linear-gradient(135deg, #f8d7da, #f5c6cb); color: #721c24; border: 1px solid #f5c6cb; }
        .empty-state { text-align: center; padding: 40px 20px; color: #999; }
        .empty-state i { font-size: 48px; color: #ddd; margin-bottom: 15px; display: block; }
        .page-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 20px; border-bottom: 2px solid #e9ecef; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .page-header h1 { color: #1a5276; font-size: 26px; font-weight: 700; }
        .page-header h1 i { color: #f39c12; margin-right: 12px; }
        .user-badge { display: flex; align-items: center; gap: 12px; background: white; padding: 8px 20px 8px 16px; border-radius: 50px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); }
        .user-badge i { font-size: 20px; color: #f39c12; }
        .user-badge span { font-weight: 500; color: #555; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 15px; } .form-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include_once 'sidebar.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-users"></i> Players Management</h1>
                <div class="user-badge">
                    <i class="fas fa-user-circle"></i>
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></span>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="card">
                <h2><i class="fas fa-user-plus"></i> Register New Player</h2>
                <form action="players.php" method="POST">
                    <input type="hidden" name="form_action" value="register">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="student_id"><i class="fas fa-id-card"></i> Student ID</label>
                            <input type="text" id="student_id" name="student_id" required placeholder="e.g., Vd19-5803-2023" value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>">
                            <small>Format: e.g., Vd19-5803-2023</small>
                        </div>
                        <div class="form-group">
                            <label for="full_name"><i class="fas fa-user"></i> Full Name</label>
                            <input type="text" id="full_name" name="full_name" required placeholder="Enter full name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="gender"><i class="fas fa-venus-mars"></i> Gender</label>
                            <select id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo (($_POST['gender'] ?? '') == 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo (($_POST['gender'] ?? '') == 'Female') ? 'selected' : ''; ?>>Female</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="date_of_birth"><i class="fas fa-calendar-alt"></i> Date of Birth</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" required value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="faculty"><i class="fas fa-university"></i> Faculty</label>
                            <input type="text" id="faculty" name="faculty" required placeholder="e.g., School of Engineering" value="<?php echo htmlspecialchars($_POST['faculty'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="course"><i class="fas fa-graduation-cap"></i> Course</label>
                            <input type="text" id="course" name="course" required placeholder="e.g., Computer Science" value="<?php echo htmlspecialchars($_POST['course'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="year_of_study"><i class="fas fa-layer-group"></i> Year of Study</label>
                            <input type="number" id="year_of_study" name="year_of_study" min="1" max="6" required placeholder="1" value="<?php echo htmlspecialchars($_POST['year_of_study'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="team"><i class="fas fa-flag"></i> Team</label>
                            <select id="team" name="team" required>
                                <option value="">Select Team</option>
                                <option value="Football" <?php echo (($_POST['team'] ?? '') == 'Football') ? 'selected' : ''; ?>>⚽ Football</option>
                                <option value="Basketball" <?php echo (($_POST['team'] ?? '') == 'Basketball') ? 'selected' : ''; ?>>🏀 Basketball</option>
                                <option value="Volleyball" <?php echo (($_POST['team'] ?? '') == 'Volleyball') ? 'selected' : ''; ?>>🏐 Volleyball</option>
                                <option value="Athletics" <?php echo (($_POST['team'] ?? '') == 'Athletics') ? 'selected' : ''; ?>>🏃 Athletics</option>
                                <option value="Rugby" <?php echo (($_POST['team'] ?? '') == 'Rugby') ? 'selected' : ''; ?>>🏉 Rugby</option>
                                <option value="Netball" <?php echo (($_POST['team'] ?? '') == 'Netball') ? 'selected' : ''; ?>>🥅 Netball</option>
                                <option value="Handball" <?php echo (($_POST['team'] ?? '') == 'Handball') ? 'selected' : ''; ?>>🤾 Handball</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                            <input type="tel" id="phone" name="phone" required placeholder="0712345678" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                            <small>Enter 10-15 digits only</small>
                        </div>
                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" id="email" name="email" required placeholder="student@machakos.ac.ke" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Register Player
                    </button>
                </form>
            </div>

            <div class="card">
                <h2><i class="fas fa-list"></i> Registered Players</h2>
                <?php if (!empty($players)): ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr><th>#</th><th>Student ID</th><th>Full Name</th><th>Team</th><th>Faculty</th><th>Year</th><th>Status</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php $i = 1; foreach ($players as $player): 
                                    $hasOutstanding = !empty(getOutstandingItems($player['id']));
                                ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($player['student_id'] ?? ''); ?></strong></td>
                                    <td><?php echo htmlspecialchars($player['full_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($player['team'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($player['faculty'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($player['year_of_study'] ?? ''); ?></td>
                                    <td>
                                        <?php if ($hasOutstanding): ?>
                                            <span class="badge badge-warning">⏳ Pending</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">✅ Cleared</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="clearance.php?player_id=<?php echo $player['id']; ?>" class="btn btn-info btn-sm">
                                            <i class="fas fa-clipboard-check"></i> Clearance
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No players registered yet.</p>
                        <small style="color: #bbb;">Use the form above to register your first player.</small>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>