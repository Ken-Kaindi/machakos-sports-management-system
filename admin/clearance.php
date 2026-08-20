<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

// ===== MAIN LOGIC =====

// Get player ID from URL
$player_id = isset($_GET['player_id']) ? (int)$_GET['player_id'] : 0;
$player = null;
$items = getAllIssuedItems();
$clearance_status = [];
$success = null;
$error = null;

// Get player details
if ($player_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM players WHERE id = ?");
        $stmt->execute([$player_id]);
        $player = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($player) {
            $clearance_status = getPlayerClearanceStatus($player_id);
        } else {
            $error = "Player not found. Please select a valid player.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
        error_log($e->getMessage());
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ISSUE ITEM
    if (isset($_POST['issue_item']) && $player_id > 0) {
        $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        
        if ($item_id <= 0) {
            $error = "Please select a valid item.";
        } elseif ($player_id <= 0) {
            $error = "No player selected.";
        } else {
            if (issueItemToPlayer($player_id, $item_id)) {
                $success = "Item issued successfully!";
                $clearance_status = getPlayerClearanceStatus($player_id);
                $items = getAllIssuedItems();
                if (function_exists('logActivity')) {
                    logActivity('item_issued', "Issued item to player: " . $player['full_name']);
                }
            } else {
                $error = "Failed to issue item. Please check item availability.";
            }
        }
    }
    
    // RETURN ITEM
    if (isset($_POST['return_item']) && $player_id > 0) {
        $player_item_id = isset($_POST['player_item_id']) ? (int)$_POST['player_item_id'] : 0;
        
        if ($player_item_id <= 0) {
            $error = "Invalid item selected.";
        } else {
            try {
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("SELECT item_id FROM player_items WHERE id = ? AND player_id = ? AND is_returned = 0");
                $stmt->execute([$player_item_id, $player_id]);
                $record = $stmt->fetch();
                
                if ($record) {
                    $stmt = $pdo->prepare("UPDATE player_items SET is_returned = 1, date_returned = NOW() WHERE id = ?");
                    $stmt->execute([$player_item_id]);
                    
                    $stmt = $pdo->prepare("UPDATE items SET quantity = quantity + 1 WHERE id = ?");
                    $stmt->execute([$record['item_id']]);
                    
                    $pdo->commit();
                    
                    $success = "Item marked as returned successfully!";
                    $clearance_status = getPlayerClearanceStatus($player_id);
                    $items = getAllIssuedItems();
                    if (function_exists('logActivity')) {
                        logActivity('item_returned', "Item returned by player: " . $player['full_name']);
                    }
                } else {
                    $pdo->rollBack();
                    $error = "Item not found or already returned.";
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "Database error: " . $e->getMessage();
                error_log($e->getMessage());
            }
        }
    }
}

// If no player selected, show all players with clearance status
if ($player_id == 0) {
    try {
        $stmt = $pdo->query("
            SELECT 
                p.*,
                COUNT(pi.id) as total_items,
                SUM(CASE WHEN pi.is_returned = 0 THEN 1 ELSE 0 END) as outstanding_items,
                SUM(CASE WHEN pi.is_returned = 1 THEN 1 ELSE 0 END) as returned_items
            FROM players p
            LEFT JOIN player_items pi ON p.id = pi.player_id
            GROUP BY p.id
            ORDER BY p.full_name
        ");
        $all_players = $stmt->fetchAll();
    } catch (PDOException $e) {
        $all_players = [];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clearance - Machakos University</title>
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
            padding: 25px 28px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.04);
            margin-bottom: 25px;
            border: 1px solid #f0f0f0;
            transition: box-shadow 0.3s ease;
        }
        .card:hover { box-shadow: 0 8px 30px rgba(0,0,0,0.06); }
        .card-title {
            color: #1a5276;
            font-size: 17px;
            font-weight: 600;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-title i { color: #f39c12; }
        
        /* ============================================
           PLAYER INFO
           ============================================ */
        .player-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px 20px;
            background: #f8fafc;
            padding: 18px 22px;
            border-radius: 12px;
            margin-bottom: 15px;
        }
        .player-info-grid p {
            margin: 0;
            font-size: 14px;
            color: #444;
        }
        .player-info-grid strong {
            color: #1a5276;
            font-weight: 600;
        }
        
        /* ============================================
           FORM
           ============================================ */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            align-items: end;
        }
        .form-group { margin-bottom: 0; }
        .form-group label {
            display: block;
            font-weight: 500;
            color: #333;
            margin-bottom: 5px;
            font-size: 13px;
        }
        .form-group label i { margin-right: 6px; color: #f39c12; }
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
        .form-group select:focus {
            border-color: #f39c12;
            background: white;
            outline: none;
            box-shadow: 0 0 0 4px rgba(243,156,18,0.08);
        }
        
        .btn {
            padding: 10px 24px;
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
        .btn-success {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(39,174,96,0.25);
        }
        .btn-secondary {
            background: #e9ecef;
            color: #555;
        }
        .btn-secondary:hover {
            background: #dde1e6;
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
        .badge-warning {
            background: linear-gradient(135deg, #fff3cd, #ffe69b);
            color: #856404;
        }
        .badge-info {
            background: linear-gradient(135deg, #d1ecf1, #8fd3dd);
            color: #0c5460;
        }
        
        /* ============================================
           PLAYER SELECT GRID
           ============================================ */
        .player-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }
        .player-card {
            background: #f8fafc;
            padding: 18px 20px;
            border-radius: 12px;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .player-card:hover {
            background: white;
            border-color: #f39c12;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
        }
        .player-card a {
            text-decoration: none;
            color: #1a5276;
            display: block;
        }
        .player-card .name { font-weight: 600; font-size: 15px; }
        .player-card .details { font-size: 13px; color: #666; margin-top: 3px; }
        .player-card .status-badge { margin-top: 8px; display: inline-block; }
        
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
            .player-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 480px) {
            .player-grid { grid-template-columns: 1fr; }
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
                    <li class="active"><a href="clearance.php"><i class="fas fa-clipboard-check"></i> Clearance</a></li>
                    <li><a href="issue_item.php"><i class="fas fa-plus-circle"></i> Issue New Item</a></li>
                    <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                    <li><a href="manage_users.php"><i class="fas fa-user-cog"></i> Manage Users</a></li>
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
                <h1><i class="fas fa-clipboard-check"></i> Player Clearance</h1>
                <div class="user-badge">
                    <i class="fas fa-user-circle"></i>
                    <span>Welcome, <?php echo isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'Admin'; ?></span>
                </div>
            </div>

            <!-- Alerts -->
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($player_id == 0): ?>
                <!-- Player Selection -->
                <div class="card">
                    <div class="card-title"><i class="fas fa-users"></i> Select a Player</div>
                    <p style="color: #666; margin-bottom: 15px; font-size: 14px;">Click on a player to view and manage their clearance status:</p>
                    
                    <?php if (!empty($all_players)): ?>
                        <div class="player-grid">
                            <?php foreach ($all_players as $p): ?>
                                <div class="player-card">
                                    <a href="clearance.php?player_id=<?php echo $p['id']; ?>">
                                        <div class="name">
                                            <i class="fas fa-user" style="color: #f39c12; margin-right: 6px;"></i>
                                            <?php echo htmlspecialchars($p['full_name']); ?>
                                        </div>
                                        <div class="details">
                                            <strong>ID:</strong> <?php echo htmlspecialchars($p['student_id']); ?> 
                                            | <strong>Team:</strong> <?php echo htmlspecialchars($p['team']); ?>
                                        </div>
                                        <div class="details">
                                            <strong>Items:</strong> <?php echo $p['total_items'] ?? 0; ?> 
                                            (<?php echo $p['outstanding_items'] ?? 0; ?> pending)
                                        </div>
                                        <span class="status-badge badge <?php 
                                            echo ($p['outstanding_items'] ?? 0) > 0 ? 'badge-warning' : (($p['total_items'] ?? 0) > 0 ? 'badge-success' : 'badge-info'); 
                                        ?>">
                                            <?php 
                                            if (($p['outstanding_items'] ?? 0) > 0) {
                                                echo '⏳ ' . $p['outstanding_items'] . ' pending';
                                            } elseif (($p['total_items'] ?? 0) > 0) {
                                                echo '✅ Cleared';
                                            } else {
                                                echo '📭 No items';
                                            }
                                            ?>
                                        </span>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>No players found.</p>
                            <a href="players.php" style="color: #f39c12; text-decoration: none; font-weight: 500;">
                                <i class="fas fa-user-plus"></i> Register a player
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Player Information -->
                <div class="card">
                    <div class="card-title"><i class="fas fa-user"></i> Player Information</div>
                    <?php if ($player): ?>
                        <div class="player-info-grid">
                            <p><strong>Student ID:</strong> <?php echo htmlspecialchars($player['student_id'] ?? 'N/A'); ?></p>
                            <p><strong>Full Name:</strong> <?php echo htmlspecialchars($player['full_name'] ?? 'N/A'); ?></p>
                            <p><strong>Team:</strong> <?php echo htmlspecialchars($player['team'] ?? 'N/A'); ?></p>
                            <p><strong>Faculty:</strong> <?php echo htmlspecialchars($player['faculty'] ?? 'N/A'); ?></p>
                            <p><strong>Course:</strong> <?php echo htmlspecialchars($player['course'] ?? 'N/A'); ?></p>
                        </div>
                        <a href="clearance.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to All Players
                        </a>
                    <?php else: ?>
                        <p>Player not found.</p>
                        <a href="clearance.php" class="btn btn-secondary">View All Players</a>
                    <?php endif; ?>
                </div>
                
                <?php if ($player): ?>
                    <!-- Issue New Item -->
                    <div class="card">
                        <div class="card-title"><i class="fas fa-plus-circle"></i> Issue New Item</div>
                        <form action="clearance.php?player_id=<?php echo $player_id; ?>" method="POST">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="item_id"><i class="fas fa-box"></i> Select Item</label>
                                    <select id="item_id" name="item_id" required>
                                        <option value="">-- Select an item --</option>
                                        <?php foreach ($items as $item): ?>
                                            <option value="<?php echo $item['id']; ?>">
                                                <?php echo htmlspecialchars($item['item_name']); ?> (<?php echo $item['quantity']; ?> available)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div style="display: flex; gap: 10px; align-items: center; padding-bottom: 4px;">
                                    <button type="submit" name="issue_item" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Issue Item
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Clearance Status -->
                    <div class="card">
                        <div class="card-title"><i class="fas fa-clipboard-list"></i> Clearance Status</div>
                        <?php if (!empty($clearance_status)): ?>
                            <div class="table-wrapper">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Item Name</th>
                                            <th>Date Issued</th>
                                            <th>Issued By</th>
                                            <th>Status</th>
                                            <th>Date Returned</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($clearance_status as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                            <td><?php echo date('Y-m-d H:i', strtotime($item['date_issued'])); ?></td>
                                            <td><?php echo htmlspecialchars($item['issued_by_name'] ?? 'System'); ?></td>
                                            <td>
                                                <?php if ($item['is_returned']): ?>
                                                    <span class="badge badge-success">✅ Returned</span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">⏳ Not Returned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo $item['date_returned'] ? date('Y-m-d H:i', strtotime($item['date_returned'])) : 'N/A'; ?>
                                            </td>
                                            <td>
                                                <?php if (!$item['is_returned']): ?>
                                                    <form action="clearance.php?player_id=<?php echo $player_id; ?>" method="POST" style="display: inline;">
                                                        <input type="hidden" name="player_item_id" value="<?php echo $item['id']; ?>">
                                                        <button type="submit" name="return_item" class="btn btn-success btn-sm">
                                                            <i class="fas fa-undo"></i> Mark Returned
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span style="color: #999; font-size: 12px;">Completed</span>
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
                                <p>No items have been issued to this player yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Mobile sidebar toggle
        document.querySelector('.sidebar-menu li a')?.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                document.getElementById('sidebar').classList.remove('open');
            }
        });
    </script>
</body>
</html>