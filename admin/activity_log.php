<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

// Get filter parameters
$action_filter = isset($_GET['action']) ? $_GET['action'] : 'all';
$username_filter = isset($_GET['username']) ? trim($_GET['username']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Get data
$total_records = getActivityCount($action_filter, $username_filter, $date_from, $date_to);
$total_pages = ceil($total_records / $limit);
$activities = getFilteredActivities($action_filter, $username_filter, $date_from, $date_to, $limit, $offset);
$action_types = getActionTypes();

// Handle clear logs
if (isset($_POST['clear_logs'])) {
    $days = (int)($_POST['days'] ?? 90);
    $deleted = cleanOldActivities($days);
    $success = "Deleted $deleted old activity records.";
    $total_records = getActivityCount($action_filter, $username_filter, $date_from, $date_to);
    $total_pages = ceil($total_records / $limit);
    $activities = getFilteredActivities($action_filter, $username_filter, $date_from, $date_to, $limit, $offset);
}

// Handle export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="activity_log_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'User', 'Action', 'Details', 'IP Address', 'Date']);
    $export_data = getFilteredActivities($action_filter, $username_filter, $date_from, $date_to, 10000, 0);
    foreach ($export_data as $row) {
        fputcsv($output, [
            $row['id'],
            $row['username'] ?? 'Guest',
            $row['action'],
            $row['details'] ?? '',
            $row['ip_address'] ?? '',
            $row['created_at']
        ]);
    }
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - Machakos University</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .app-container { display: flex; min-height: 100vh; }
        .main-content { flex: 1; padding: 25px 30px; margin-left: 260px; background: #f0f2f5; }
        .page-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 20px; border-bottom: 2px solid #e9ecef; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .page-header h1 { color: #1a5276; font-size: 26px; font-weight: 700; }
        .page-header h1 i { color: #f39c12; margin-right: 12px; }
        .user-badge { display: flex; align-items: center; gap: 12px; background: white; padding: 8px 20px 8px 16px; border-radius: 50px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); }
        .user-badge i { font-size: 20px; color: #f39c12; }
        .user-badge span { font-weight: 500; color: #555; }
        .card { background: white; border-radius: 16px; padding: 28px 30px; box-shadow: 0 2px 15px rgba(0,0,0,0.04); margin-bottom: 25px; border: 1px solid #f0f0f0; }
        .card h2 { color: #1a5276; font-size: 18px; font-weight: 600; margin-bottom: 20px; }
        .filter-bar { display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; margin-bottom: 20px; }
        .filter-group { flex: 1; min-width: 150px; }
        .filter-group label { display: block; font-size: 12px; font-weight: 600; color: #555; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px; }
        .filter-group input, .filter-group select { width: 100%; padding: 8px 12px; border: 1.5px solid #e9ecef; border-radius: 8px; font-size: 14px; background: #fafbfc; }
        .filter-group input:focus, .filter-group select:focus { border-color: #f39c12; outline: none; box-shadow: 0 0 0 4px rgba(243,156,18,0.08); }
        .btn { padding: 8px 16px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg, #1a5276, #2980b9); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(26,82,118,0.25); }
        .btn-success { background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; }
        .btn-success:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(39,174,96,0.25); }
        .btn-danger { background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; }
        .btn-danger:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(231,76,60,0.25); }
        .btn-secondary { background: #e9ecef; color: #555; }
        .btn-secondary:hover { background: #dde1e6; }
        .btn-sm { padding: 5px 12px; font-size: 12px; }
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #f0f0f0; }
        th { background: linear-gradient(135deg, #f8f9fa, #eef2f7); color: #333; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.3px; }
        tr:hover td { background: #f8fafc; }
        .badge { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
        .badge-blue { background: #dbeafe; color: #2563eb; }
        .badge-green { background: #d1fae5; color: #059669; }
        .badge-red { background: #fee2e2; color: #dc2626; }
        .badge-yellow { background: #fef3c7; color: #d97706; }
        .badge-gray { background: #f3f4f6; color: #6b7280; }
        .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 20px; flex-wrap: wrap; }
        .pagination a, .pagination span { padding: 6px 14px; border-radius: 6px; border: 1px solid #ddd; text-decoration: none; color: #333; font-size: 14px; }
        .pagination a:hover { background: #2980b9; color: white; border-color: #2980b9; }
        .pagination .active { background: #1a5276; color: white; border-color: #1a5276; }
        .pagination .disabled { opacity: 0.5; cursor: not-allowed; }
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .stat-box { background: white; padding: 15px 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); text-align: center; border: 1px solid #f0f0f0; }
        .stat-box .number { font-size: 28px; font-weight: 700; color: #1a5276; }
        .stat-box .label { font-size: 12px; color: #888; text-transform: uppercase; margin-top: 3px; }
        .empty-state { text-align: center; padding: 40px 20px; color: #999; }
        .empty-state i { font-size: 48px; color: #ddd; margin-bottom: 15px; display: block; }
        .alert { padding: 14px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; }
        .alert-success { background: linear-gradient(135deg, #d4edda, #b7ebc5); color: #0b5e2e; border: 1px solid #b7ebc5; }
        .text-truncate { max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: inline-block; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 15px; } .filter-bar { flex-direction: column; } .filter-group { width: 100%; } }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include_once 'sidebar.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-history"></i> Activity Log</h1>
                <div class="user-badge">
                    <i class="fas fa-user-circle"></i>
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></span>
                </div>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div class="stats-row">
                <div class="stat-box"><div class="number"><?php echo number_format($total_records); ?></div><div class="label">Total Activities</div></div>
                <div class="stat-box"><div class="number"><?php echo number_format(count($activities)); ?></div><div class="label">Showing (This Page)</div></div>
                <div class="stat-box"><div class="number"><?php echo number_format($total_pages); ?></div><div class="label">Total Pages</div></div>
            </div>

            <div class="card">
                <h2><i class="fas fa-filter"></i> Filters</h2>
                <form action="activity_log.php" method="GET" class="filter-bar">
                    <div class="filter-group">
                        <label for="action">Action</label>
                        <select id="action" name="action">
                            <option value="all">All Actions</option>
                            <?php foreach ($action_types as $action): ?>
                                <option value="<?php echo htmlspecialchars($action); ?>" <?php echo $action_filter === $action ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(str_replace('_', ' ', $action)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" placeholder="Search username..." value="<?php echo htmlspecialchars($username_filter); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="date_from">Date From</label>
                        <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="date_to">Date To</label>
                        <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                        <a href="activity_log.php" class="btn btn-secondary"><i class="fas fa-undo"></i> Reset</a>
                        <a href="?export=csv<?php 
                            echo $action_filter !== 'all' ? '&action=' . urlencode($action_filter) : '';
                            echo $username_filter ? '&username=' . urlencode($username_filter) : '';
                            echo $date_from ? '&date_from=' . urlencode($date_from) : '';
                            echo $date_to ? '&date_to=' . urlencode($date_to) : '';
                        ?>" class="btn btn-success"><i class="fas fa-file-csv"></i> Export</a>
                    </div>
                </form>
            </div>

            <div class="card">
                <h2><i class="fas fa-list"></i> Activity History</h2>
                <?php if (!empty($activities)): ?>
                    <div class="table-wrapper">
                        <table>
                            <thead><tr><th>#</th><th>User</th><th>Action</th><th>Details</th><th>IP Address</th><th>Date</th></tr></thead>
                            <tbody>
                                <?php $counter = $offset + 1; foreach ($activities as $log): ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($log['username'] ?? 'Guest'); ?></strong></td>
                                    <td>
                                        <span class="badge <?php 
                                            echo in_array($log['action'], ['login']) ? 'badge-blue' : 
                                                (in_array($log['action'], ['logout', 'player_deleted', 'user_deleted']) ? 'badge-red' : 
                                                (in_array($log['action'], ['player_registered', 'item_returned', 'user_added']) ? 'badge-green' : 
                                                (in_array($log['action'], ['item_issued']) ? 'badge-yellow' : 'badge-gray')));
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $log['action'])); ?>
                                        </span>
                                    </td>
                                    <td class="text-truncate" title="<?php echo htmlspecialchars($log['details'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($log['details'] ?? ''); ?>
                                    </td>
                                    <td><code style="background:#f0f0f0; padding:2px 6px; border-radius:4px; font-size:12px;"><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></code></td>
                                    <td style="font-size:13px;"><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&action=<?php echo urlencode($action_filter); ?>&username=<?php echo urlencode($username_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>"><i class="fas fa-chevron-left"></i> Prev</a>
                            <?php else: ?>
                                <span class="disabled"><i class="fas fa-chevron-left"></i> Prev</span>
                            <?php endif; ?>
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>&action=<?php echo urlencode($action_filter); ?>&username=<?php echo urlencode($username_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&action=<?php echo urlencode($action_filter); ?>&username=<?php echo urlencode($username_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">Next <i class="fas fa-chevron-right"></i></a>
                            <?php else: ?>
                                <span class="disabled">Next <i class="fas fa-chevron-right"></i></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state"><i class="fas fa-inbox"></i><p>No activity logs found.</p></div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>