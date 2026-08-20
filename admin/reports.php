<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

// ===== GET FILTERS =====
$filter = isset($_GET['filter']) ? htmlspecialchars($_GET['filter']) : 'all';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : null;
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : null;
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'items';

// ===== GENERATE REPORT =====
$error = null;
$report_data = [];

try {
    if ($report_type === 'players') {
        $report_data = generatePlayersReport();
    } else {
        $report_data = generateItemsReport($filter, $date_from, $date_to);
    }
} catch (Exception $e) {
    $error = "Failed to generate report. Please try again.";
    $report_data = [];
}

// ===== CALCULATE SUMMARY =====
$summary = [
    'total_items' => 0,
    'total_issued' => 0,
    'total_returned' => 0,
    'total_outstanding' => 0
];

if ($report_type === 'items') {
    foreach ($report_data as $item) {
        $summary['total_items']++;
        $summary['total_issued'] += (int)($item['total_issued'] ?? 0);
        $summary['total_returned'] += (int)($item['total_returned'] ?? 0);
        $summary['total_outstanding'] += (int)($item['total_outstanding'] ?? 0);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Machakos University</title>
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
        .card-subtitle {
            color: #888;
            font-size: 14px;
            margin-bottom: 20px;
            display: block;
        }
        
        /* ============================================
           FILTER FORM
           ============================================ */
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 18px;
            align-items: end;
        }
        .filter-group { margin-bottom: 0; }
        .filter-group label {
            display: block;
            font-weight: 500;
            color: #333;
            margin-bottom: 5px;
            font-size: 13px;
        }
        .filter-group label i { margin-right: 6px; color: #f39c12; }
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid #e9ecef;
            border-radius: 10px;
            font-size: 14px;
            background: #fafbfc;
            transition: all 0.3s ease;
            color: #333;
        }
        .filter-group input:focus,
        .filter-group select:focus {
            border-color: #f39c12;
            background: white;
            outline: none;
            box-shadow: 0 0 0 4px rgba(243,156,18,0.08);
        }
        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            padding-bottom: 2px;
        }
        
        /* ============================================
           BUTTONS
           ============================================ */
        .btn {
            padding: 10px 22px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #1a5276, #2980b9);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(26,82,118,0.25);
        }
        .btn-secondary {
            background: #e9ecef;
            color: #555;
        }
        .btn-secondary:hover {
            background: #dde1e6;
            transform: translateY(-2px);
        }
        .btn-success {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(39,174,96,0.25);
        }
        .btn-info {
            background: linear-gradient(135deg, #2980b9, #3498db);
            color: white;
        }
        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(41,128,185,0.25);
        }
        .btn-sm { padding: 6px 14px; font-size: 12px; border-radius: 8px; }
        .btn i { font-size: 14px; }
        
        /* ============================================
           SUMMARY STATS
           ============================================ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: white;
            padding: 20px 22px;
            border-radius: 12px;
            border: 1px solid #f0f0f0;
            text-align: center;
            transition: all 0.3s ease;
        }
        .stat-box:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.06);
        }
        .stat-box .stat-icon {
            font-size: 28px;
            margin-bottom: 8px;
            display: block;
        }
        .stat-box .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #1a5276;
            display: block;
        }
        .stat-box .stat-label {
            font-size: 13px;
            color: #888;
            margin-top: 3px;
            display: block;
        }
        .stat-box.blue .stat-icon { color: #2980b9; }
        .stat-box.green .stat-icon { color: #27ae60; }
        .stat-box.orange .stat-icon { color: #f39c12; }
        .stat-box.purple .stat-icon { color: #8e44ad; }
        
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
        .badge-danger {
            background: linear-gradient(135deg, #f8d7da, #f5a3a8);
            color: #721c24;
        }
        .badge-info {
            background: linear-gradient(135deg, #d1ecf1, #8fd3dd);
            color: #0c5460;
        }
        
        /* ============================================
           TABLE ACTIONS
           ============================================ */
        .table-actions {
            display: flex;
            gap: 10px;
            margin-top: 18px;
            flex-wrap: wrap;
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
        @media (max-width: 992px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s ease; width: 280px; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 15px; }
            .filter-form { grid-template-columns: 1fr 1fr; }
            .page-header { flex-direction: column; align-items: flex-start; }
        }
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
            .filter-form { grid-template-columns: 1fr; }
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
                    <li class="active"><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
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
                <h1><i class="fas fa-chart-bar"></i> Reports</h1>
                <div class="user-badge">
                    <i class="fas fa-user-circle"></i>
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></span>
                </div>
            </div>

            <!-- Error Alert -->
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Filter Card -->
            <div class="card">
                <div class="card-title"><i class="fas fa-sliders-h"></i> Report Filters</div>
                <form action="reports.php" method="GET" class="filter-form">
                    <div class="filter-group">
                        <label for="report_type"><i class="fas fa-file-alt"></i> Report Type</label>
                        <select id="report_type" name="report_type">
                            <option value="items" <?php echo ($report_type === 'items') ? 'selected' : ''; ?>>Items Report</option>
                            <option value="players" <?php echo ($report_type === 'players') ? 'selected' : ''; ?>>Players Report</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="filter"><i class="fas fa-filter"></i> Status</label>
                        <select id="filter" name="filter">
                            <option value="all" <?php echo ($filter === 'all') ? 'selected' : ''; ?>>All Items</option>
                            <option value="outstanding" <?php echo ($filter === 'outstanding') ? 'selected' : ''; ?>>Outstanding Only</option>
                            <option value="returned" <?php echo ($filter === 'returned') ? 'selected' : ''; ?>>Returned Only</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="date_from"><i class="fas fa-calendar-alt"></i> Date From</label>
                        <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from ?? ''); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="date_to"><i class="fas fa-calendar-alt"></i> Date To</label>
                        <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to ?? ''); ?>">
                    </div>
                    
                    <div class="filter-group" style="display: flex; gap: 10px; align-items: center; padding-bottom: 2px;">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Apply Filter</button>
                        <a href="reports.php" class="btn btn-secondary"><i class="fas fa-undo"></i> Reset</a>
                    </div>
                </form>
            </div>

            <!-- Summary Stats -->
            <?php if ($report_type === 'items'): ?>
                <div class="stats-grid">
                    <div class="stat-box blue">
                        <span class="stat-icon"><i class="fas fa-boxes"></i></span>
                        <span class="stat-number"><?php echo $summary['total_items']; ?></span>
                        <span class="stat-label">Total Items</span>
                    </div>
                    <div class="stat-box green">
                        <span class="stat-icon"><i class="fas fa-check-circle"></i></span>
                        <span class="stat-number"><?php echo $summary['total_issued']; ?></span>
                        <span class="stat-label">Total Issued</span>
                    </div>
                    <div class="stat-box purple">
                        <span class="stat-icon"><i class="fas fa-undo-alt"></i></span>
                        <span class="stat-number"><?php echo $summary['total_returned']; ?></span>
                        <span class="stat-label">Total Returned</span>
                    </div>
                    <div class="stat-box orange">
                        <span class="stat-icon"><i class="fas fa-clock"></i></span>
                        <span class="stat-number"><?php echo $summary['total_outstanding']; ?></span>
                        <span class="stat-label">Outstanding</span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Report Table -->
            <div class="card">
                <div class="card-title">
                    <i class="fas fa-table"></i> 
                    <?php echo $report_type === 'players' ? 'Players Report' : 'Issued Items Report'; ?>
                </div>
                
                <?php if (!empty($report_data)): ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <?php if ($report_type === 'players'): ?>
                                        <th>Student ID</th>
                                        <th>Full Name</th>
                                        <th>Team</th>
                                        <th>Items Issued</th>
                                        <th>Items Returned</th>
                                        <th>Outstanding</th>
                                        <th>Status</th>
                                    <?php else: ?>
                                        <th>Item Name</th>
                                        <th>Total Issued</th>
                                        <th>Total Returned</th>
                                        <th>Outstanding</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($report_type === 'players'): ?>
                                    <?php foreach ($report_data as $player): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($player['student_id'] ?? 'N/A'); ?></strong></td>
                                        <td><?php echo htmlspecialchars($player['full_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($player['team'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($player['items_issued'] ?? 0); ?></td>
                                        <td><?php echo htmlspecialchars($player['items_returned'] ?? 0); ?></td>
                                        <td><?php echo htmlspecialchars($player['items_outstanding'] ?? 0); ?></td>
                                        <td>
                                            <?php if (($player['items_outstanding'] ?? 0) > 0): ?>
                                                <span class="badge badge-warning">⏳ Pending</span>
                                            <?php else: ?>
                                                <span class="badge badge-success">✅ Cleared</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <?php foreach ($report_data as $item): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($item['item_name'] ?? 'N/A'); ?></strong></td>
                                        <td><?php echo htmlspecialchars($item['total_issued'] ?? 0); ?></td>
                                        <td><?php echo htmlspecialchars($item['total_returned'] ?? 0); ?></td>
                                        <td>
                                            <span class="badge <?php echo ($item['total_outstanding'] ?? 0) > 0 ? 'badge-warning' : 'badge-success'; ?>">
                                                <?php echo htmlspecialchars($item['total_outstanding'] ?? 0); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Export Actions -->
                    <div class="table-actions">
                        <button onclick="window.print()" class="btn btn-info">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                        <button onclick="exportToExcel()" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Export to Excel
                        </button>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No data available for the selected filters.</p>
                        <small style="color: #bbb;">Try adjusting your filter criteria.</small>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function exportToExcel() {
            let table = document.querySelector('table');
            if (!table) return;
            
            let html = table.outerHTML;
            
            let style = `
                <style>
                    th { background-color: #1a5276; color: white; padding: 10px; }
                    td, th { border: 1px solid #ddd; padding: 10px; }
                    .badge-warning { color: #856404; background-color: #fff3cd; }
                    .badge-success { color: #155724; background-color: #d4edda; }
                </style>
            `;
            
            let excelHtml = `
                <html>
                    <head>
                        <meta charset="UTF-8">
                        ${style}
                    </head>
                    <body>
                        <h1>Sports Management Report</h1>
                        <p>Generated: ${new Date().toLocaleString()}</p>
                        ${html}
                    </body>
                </html>
            `;
            
            let url = 'data:application/vnd.ms-excel;charset=utf-8,' + encodeURIComponent(excelHtml);
            let link = document.createElement('a');
            link.href = url;
            link.download = 'report_' + new Date().toISOString().slice(0,10) + '.xls';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>