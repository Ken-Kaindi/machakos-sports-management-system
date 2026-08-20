
<?php
/**
 * ============================================================
 * MACHakos UNIVERSITY SPORTS MANAGEMENT SYSTEM V2.0
 * ADMIN DASHBOARD
 * ============================================================
 *
 * Purpose:
 * - Display dashboard figures directly from V2 database views.
 * - Keep dashboard figures synchronized with database reporting.
 * - Preserve the existing professional dashboard appearance.
 * - Use equipment_issues / equipment_returns / inventory_items.
 * - Remove obsolete player_items / items dependencies.
 *
 * Database:
 * machakos_sports_v2
 * ============================================================
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';

requireAdmin();

/* ============================================================
   SECURITY / CSRF
   ============================================================ */

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];

/* ============================================================
   FLASH MESSAGES
   ============================================================ */

$quick_success = $_SESSION['flash_success'] ?? null;
$quick_error   = $_SESSION['flash_error'] ?? null;

unset(
    $_SESSION['flash_success'],
    $_SESSION['flash_error']
);

/* ============================================================
   HELPER FUNCTIONS
   ============================================================ */

/**
 * Safely execute a dashboard query.
 */
function dashboardQuery(PDO $pdo, string $sql, array $params = [])
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log('Dashboard query failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Return a single integer from a query.
 */
function dashboardCount(PDO $pdo, string $sql, array $params = []): int
{
    $stmt = dashboardQuery($pdo, $sql, $params);

    if (!$stmt) {
        return 0;
    }

    $value = $stmt->fetchColumn();

    return is_numeric($value) ? (int)$value : 0;
}

/**
 * Escape output safely.
 */
function dashboardEscape($value): string
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

/**
 * Dashboard activity feed.
 *
 * Uses the current V2 equipment tables instead of the
 * obsolete player_items/items tables.
 */
function getDashboardRecentActivity(PDO $pdo): array
{
    try {

        $sql = "
            SELECT
                'player_registered' AS type,
                p.registration_date AS activity_date,
                CONCAT(
                    'Player registered: ',
                    p.full_name
                ) AS activity,
                NULL AS activity_user
            FROM players p

            UNION ALL

            SELECT
                'equipment_issued' AS type,
                ei.issue_date AS activity_date,
                CONCAT(
                    'Equipment issued to: ',
                    p.full_name,
                    ' (',
                    i.item_name,
                    ')'
                ) AS activity,
                u.full_name AS activity_user
            FROM equipment_issues ei
            INNER JOIN players p
                ON ei.player_id = p.id
            INNER JOIN inventory_items i
                ON ei.inventory_id = i.id
            LEFT JOIN users u
                ON ei.issued_by = u.id

            UNION ALL

            SELECT
                'equipment_returned' AS type,
                er.return_date AS activity_date,
                CONCAT(
                    'Equipment returned by: ',
                    p.full_name,
                    ' (',
                    i.item_name,
                    ')'
                ) AS activity,
                u.full_name AS activity_user
            FROM equipment_returns er
            INNER JOIN equipment_issues ei
                ON er.issue_id = ei.id
            INNER JOIN players p
                ON ei.player_id = p.id
            INNER JOIN inventory_items i
                ON ei.inventory_id = i.id
            LEFT JOIN users u
                ON er.returned_by = u.id

            ORDER BY activity_date DESC
            LIMIT 10
        ";

        $stmt = $pdo->query($sql);

        return $stmt ? $stmt->fetchAll() : [];

    } catch (PDOException $e) {

        error_log(
            'getDashboardRecentActivity failed: ' .
            $e->getMessage()
        );

        return [];
    }
}

/* ============================================================
   QUICK EQUIPMENT ISSUE
   ============================================================ */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['quick_issue'])
) {

    $postedToken = $_POST['csrf_token'] ?? '';

    if (
        empty($_SESSION['csrf_token'])
        || !hash_equals(
            $_SESSION['csrf_token'],
            $postedToken
        )
    ) {

        $_SESSION['flash_error'] =
            'Your session expired. Please refresh and try again.';

    } else {

        $player_id = (int)($_POST['player_id'] ?? 0);
        $inventory_id = (int)($_POST['inventory_id'] ?? 0);

        if ($player_id <= 0) {

            $_SESSION['flash_error'] =
                'Please select a player.';

        } elseif ($inventory_id <= 0) {

            $_SESSION['flash_error'] =
                'Please select an equipment item.';

        } else {

            try {

                /*
                 * Verify player exists.
                 */
                $playerStmt = $pdo->prepare("
                    SELECT id
                    FROM players
                    WHERE id = ?
                    LIMIT 1
                ");

                $playerStmt->execute([
                    $player_id
                ]);

                $playerExists = $playerStmt->fetchColumn();

                /*
                 * Verify inventory item exists and has stock.
                 */
                $itemStmt = $pdo->prepare("
                    SELECT id, item_name, quantity
                    FROM inventory_items
                    WHERE id = ?
                    LIMIT 1
                ");

                $itemStmt->execute([
                    $inventory_id
                ]);

                $inventoryItem = $itemStmt->fetch();

                if (!$playerExists) {

                    $_SESSION['flash_error'] =
                        'Selected player could not be found.';

                } elseif (!$inventoryItem) {

                    $_SESSION['flash_error'] =
                        'Selected equipment item could not be found.';

                } elseif ((int)$inventoryItem['quantity'] <= 0) {

                    $_SESSION['flash_error'] =
                        'The selected equipment item is out of stock.';

                } else {

                    /*
                     * Issue one unit.
                     *
                     * The database triggers/module controls stock
                     * synchronization. We therefore only create
                     * the transaction here.
                     */
                    $issueStmt = $pdo->prepare("
                        INSERT INTO equipment_issues (
                            player_id,
                            inventory_id,
                            quantity,
                            issue_date,
                            expected_return_date,
                            issued_by,
                            status,
                            remarks
                        )
                        VALUES (
                            :player_id,
                            :inventory_id,
                            1,
                            NOW(),
                            DATE_ADD(CURDATE(), INTERVAL 30 DAY),
                            :issued_by,
                            'Issued',
                            :remarks
                        )
                    ");

                    $issueStmt->execute([
                        ':player_id'   => $player_id,
                        ':inventory_id'=> $inventory_id,
                        ':issued_by'   => (int)(
                            $_SESSION['user_id']
                            ?? $_SESSION['id']
                            ?? 0
                        ),
                        ':remarks'     => 'Quick issue from dashboard'
                    ]);

                    $_SESSION['flash_success'] =
                        'Equipment issued successfully to the selected player.';
                }

            } catch (PDOException $e) {

                error_log(
                    'Quick equipment issue failed: ' .
                    $e->getMessage()
                );

                $_SESSION['flash_error'] =
                    'Unable to issue equipment. Please check the equipment stock and try again.';
            }
        }
    }

    /*
     * Post/Redirect/Get.
     */
    header('Location: dashboard.php');
    exit;
}

/* ============================================================
   DASHBOARD SUMMARY
   ============================================================
 *
 * IMPORTANT:
 * The primary dashboard figures below come directly from
 * view_dashboard_summary.
 *
 * This prevents the dashboard from calculating its own
 * competing version of the numbers.
 * ============================================================
 */

$summary = [
    'total_users'            => 0,
    'total_players'          => 0,
    'total_inventory_items'  => 0,
    'total_stock'            => 0,
    'equipment_issued'       => 0,
    'equipment_returned'     => 0,
    'tournaments'            => 0,
    'pending_clearance'      => 0
];

try {

    $stmt = $pdo->query("
        SELECT
            total_users,
            total_players,
            total_inventory_items,
            total_stock,
            equipment_issued,
            equipment_returned,
            tournaments,
            pending_clearance
        FROM view_dashboard_summary
        LIMIT 1
    ");

    $row = $stmt->fetch();

    if ($row) {
        foreach ($summary as $key => $default) {
            if (array_key_exists($key, $row)) {
                $summary[$key] = (int)$row[$key];
            }
        }
    }

} catch (PDOException $e) {

    error_log(
        'view_dashboard_summary failed: ' .
        $e->getMessage()
    );
}

/* ============================================================
   DASHBOARD STATISTICS
   ============================================================ */

$totalUsers           = $summary['total_users'];
$totalPlayers         = $summary['total_players'];
$totalInventoryItems  = $summary['total_inventory_items'];
$totalStock           = $summary['total_stock'];
$equipmentIssued      = $summary['equipment_issued'];
$equipmentReturned    = $summary['equipment_returned'];
$totalTournaments     = $summary['tournaments'];
$pendingClearance     = $summary['pending_clearance'];

/*
 * Cleared players are calculated from the authoritative
 * clearance view.
 *
 * We deliberately do not use players - pending clearance
 * because a player with no equipment can also be "Cleared".
 */
$clearedPlayers = dashboardCount(
    $pdo,
    "
        SELECT COUNT(*)
        FROM view_player_clearance
        WHERE calculated_clearance_status = 'Cleared'
    "
);

/* ============================================================
   EQUIPMENT STATUS
   ============================================================
 *
 * Authoritative source:
 * view_equipment_issue_report
 *
 * This should agree with:
 * equipment_issued = 2
 * equipment_returned = 1
 * in the current database.
 * ============================================================
 */

$issuedStatus   = 0;
$returnedStatus = 0;
$lostStatus     = 0;
$damagedStatus  = 0;

try {

    $stmt = $pdo->query("
        SELECT
            status,
            COUNT(*) AS total
        FROM view_equipment_issue_report
        GROUP BY status
    ");

    $statusRows = $stmt->fetchAll();

    foreach ($statusRows as $row) {

        $status = strtolower(
            trim((string)$row['status'])
        );

        $count = (int)$row['total'];

        switch ($status) {

            case 'issued':
            case 'overdue':
                $issuedStatus += $count;
                break;

            case 'returned':
                $returnedStatus += $count;
                break;

            case 'lost':
                $lostStatus += $count;
                break;

            case 'damaged':
                $damagedStatus += $count;
                break;
        }
    }

} catch (PDOException $e) {

    error_log(
        'Equipment status view failed: ' .
        $e->getMessage()
    );

    /*
     * Safe fallback to dashboard summary.
     */
    $issuedStatus   = $equipmentIssued;
    $returnedStatus = $equipmentReturned;
}

/*
 * Current pending returns are currently issued equipment.
 */
$pendingReturns = $equipmentIssued;

$hasItemStatusData =
    (
        $issuedStatus
        + $returnedStatus
        + $lostStatus
        + $damagedStatus
    ) > 0;

/* ============================================================
   LOW STOCK
   ============================================================
 *
 * Use the database low-stock view instead of inventing a
 * threshold inside dashboard.php.
 * ============================================================
 */

$lowStock = dashboardCount(
    $pdo,
    "SELECT COUNT(*) FROM view_low_stock"
);

/* ============================================================
   TEAM CHART
   ============================================================ */

$hasTeamData = false;
$teamLabels = [];
$teamCounts = [];

try {

    $stmt = $pdo->query("
        SELECT
            COALESCE(NULLIF(TRIM(team), ''), 'Unassigned') AS team,
            COUNT(*) AS total
        FROM players
        GROUP BY COALESCE(NULLIF(TRIM(team), ''), 'Unassigned')
        ORDER BY total DESC, team ASC
    ");

    $teamData = $stmt->fetchAll();

    foreach ($teamData as $row) {

        $teamLabels[] = $row['team'];
        $teamCounts[] = (int)$row['total'];
    }

    $hasTeamData = !empty($teamLabels);

} catch (PDOException $e) {

    error_log(
        'Team chart query failed: ' .
        $e->getMessage()
    );
}

/* ============================================================
   MONTHLY PLAYER REGISTRATIONS
   ============================================================ */

$monthLabels = [];
$monthCounts = [];

try {

    $stmt = $pdo->query("
        SELECT
            DATE_FORMAT(registration_date, '%Y-%m') AS ym,
            DATE_FORMAT(registration_date, '%b') AS month,
            COUNT(*) AS total
        FROM players
        WHERE registration_date >= DATE_SUB(
            CURDATE(),
            INTERVAL 5 MONTH
        )
        GROUP BY
            DATE_FORMAT(registration_date, '%Y-%m'),
            DATE_FORMAT(registration_date, '%b')
        ORDER BY ym ASC
    ");

    $monthlyData = $stmt->fetchAll();

    /*
     * Always render exactly six calendar months.
     */
    $monthMap = [];

    foreach ($monthlyData as $row) {
        $monthMap[$row['ym']] = (int)$row['total'];
    }

    for ($i = 5; $i >= 0; $i--) {

        $timestamp = strtotime("-{$i} months");

        $ym = date('Y-m', $timestamp);
        $month = date('M', $timestamp);

        $monthLabels[] = $month;
        $monthCounts[] = $monthMap[$ym] ?? 0;
    }

} catch (PDOException $e) {

    error_log(
        'Monthly registrations query failed: ' .
        $e->getMessage()
    );

    for ($i = 5; $i >= 0; $i--) {

        $monthLabels[] =
            date('M', strtotime("-{$i} months"));

        $monthCounts[] = 0;
    }
}

/* ============================================================
   RECENT ACTIVITY
   ============================================================ */

$activities = getDashboardRecentActivity($pdo);

/* ============================================================
   QUICK ISSUE - AVAILABLE INVENTORY
   ============================================================ */

$availableItems = [];

try {

    $stmt = $pdo->query("
        SELECT
            id,
            asset_number,
            item_name,
            quantity
        FROM inventory_items
        WHERE quantity > 0
        ORDER BY item_name ASC
        LIMIT 100
    ");

    $availableItems = $stmt->fetchAll();

} catch (PDOException $e) {

    error_log(
        'Available inventory query failed: ' .
        $e->getMessage()
    );
}

/* ============================================================
   QUICK ISSUE - PLAYERS
   ============================================================ */

$quickPlayers = [];

try {

    $stmt = $pdo->query("
        SELECT
            id,
            full_name,
            student_id
        FROM players
        ORDER BY full_name ASC
        LIMIT 100
    ");

    $quickPlayers = $stmt->fetchAll();

} catch (PDOException $e) {

    error_log(
        'Quick players query failed: ' .
        $e->getMessage()
    );
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Dashboard - Machakos University Sports
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/dashboard.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

    <script
        src="https://cdn.jsdelivr.net/npm/chart.js"
    ></script>

    <style>

        :root {
            --sd-accent: #1d6fa5;
            --sd-accent2: #16a34a;
            --sd-warning: #b45309;
            --sd-danger: #c0311f;
        }

        body {
            transition:
                background .3s ease,
                color .3s ease;
        }

        /* ====================================================
           DARK MODE
           ==================================================== */

        body.theme-dark {
            background: #0f172a;
            color: #e2e8f0;
        }

        body.theme-dark .stat-card,
        body.theme-dark .chart-card,
        body.theme-dark .action-card,
        body.theme-dark .activities-card,
        body.theme-dark .welcome-banner,
        body.theme-dark .alert-card {
            background: #1e293b;
            color: #e2e8f0;
            border-color: #334155;
        }

        body.theme-dark select,
        body.theme-dark input {
            background: #0f172a;
            color: #e2e8f0;
            border-color: #334155;
        }

        body.theme-dark .alert-title {
            color: #f8fafc;
        }

        body.theme-dark .alert-meta {
            color: #cbd5e1;
        }

        /* ====================================================
           THEME / REFRESH
           ==================================================== */

        .theme-toggle {
            cursor: pointer;
            border: none;
            background: rgba(0, 0, 0, .06);
            border-radius: 20px;
            padding: 7px 14px;
            font-size: .85rem;
            display: flex;
            align-items: center;
            gap: 7px;
            transition:
                transform .15s ease,
                background .2s ease;
        }

        .theme-toggle:hover {
            transform: translateY(-1px);
            background: rgba(0, 0, 0, .1);
        }

        .refresh-btn i {
            transition: transform .5s ease;
        }

        .refresh-btn.spinning i {
            transform: rotate(360deg);
        }

        .last-updated {
            font-size: .75rem;
            color: #64748b;
            margin-left: 4px;
            white-space: nowrap;
        }

        /* ====================================================
           STAT CARDS
           ==================================================== */

        .stat-card {
            position: relative;
            overflow: hidden;
            cursor: default;
            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow:
                0 12px 28px rgba(0, 0, 0, .12);
        }

        .stat-card::after {
            content: '';
            position: absolute;
            inset: 0;
            background:
                linear-gradient(
                    135deg,
                    rgba(255,255,255,.24),
                    transparent 60%
                );
            opacity: 0;
            transition: opacity .3s ease;
            pointer-events: none;
        }

        .stat-card:hover::after {
            opacity: 1;
        }

        .stat-number {
            display: inline-block;
            min-width: 1ch;
        }

        /* ====================================================
           CARDS
           ==================================================== */

        .chart-card,
        .action-card,
        .activities-card {
            transition:
                box-shadow .2s ease,
                transform .2s ease;
        }

        .chart-card:hover,
        .action-card:hover {
            box-shadow:
                0 10px 24px rgba(0, 0, 0, .10);
        }

        /* ====================================================
           ALERT ANIMATION
           ==================================================== */

        .alert-card {
            animation: slideIn .35s ease;
        }

        @keyframes slideIn {

            from {
                opacity: 0;
                transform: translateY(-8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ====================================================
           TOASTS
           ==================================================== */

        .toast-wrap {
            position: fixed;
            top: 18px;
            right: 18px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .toast {
            min-width: 270px;
            max-width: 380px;
            padding: 14px 16px;
            border-radius: 11px;
            color: #fff;
            box-shadow:
                0 10px 25px rgba(0,0,0,.20);
            display: flex;
            align-items: center;
            gap: 10px;
            animation:
                toastIn .3s ease,
                toastOut .4s ease 3.6s forwards;
        }

        .toast.success {
            background: #16a34a;
        }

        .toast.error {
            background: #c0311f;
        }

        @keyframes toastIn {

            from {
                opacity: 0;
                transform: translateX(40px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes toastOut {

            to {
                opacity: 0;
                transform: translateX(40px);
            }
        }

        /* ====================================================
           SEARCHABLE SELECT
           ==================================================== */

        .searchable-select {
            position: relative;
            margin-bottom: 10px;
        }

        .searchable-select input.ss-search {
            width: 100%;
            padding: 9px 12px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            font-size: .9rem;
            box-sizing: border-box;
            margin-bottom: 6px;
        }

        .searchable-select select {
            width: 100%;
        }

        .searchable-select .ss-hint {
            font-size: .72rem;
            color: #64748b;
            margin-top: 4px;
        }

        /* ====================================================
           BUTTON EFFECT
           ==================================================== */

        .btn {
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255,255,255,.35);
            transform: translate(-50%, -50%);
            transition:
                width .4s ease,
                height .4s ease;
            pointer-events: none;
        }

        .btn:active::before {
            width: 220px;
            height: 220px;
        }

        /* ====================================================
           CHART LOADING
           ==================================================== */

        .chart-wrapper {
            position: relative;
        }

        .chart-wrapper:not(.is-loaded)::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 8px;
            background:
                linear-gradient(
                    90deg,
                    #eee 25%,
                    #f5f5f5 37%,
                    #eee 63%
                );
            background-size: 400% 100%;
            animation: shimmer 1.4s ease infinite;
            pointer-events: none;
        }

        @keyframes shimmer {

            0% {
                background-position: 100% 0;
            }

            100% {
                background-position: 0 0;
            }
        }

        /* ====================================================
           CONFIRMATION MODAL
           ==================================================== */

        .confirm-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15,23,42,.55);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            padding: 20px;
        }

        .confirm-modal-backdrop.open {
            display: flex;
        }

        .confirm-modal {
            background: #fff;
            border-radius: 14px;
            padding: 24px;
            max-width: 390px;
            width: 100%;
            box-shadow:
                0 20px 50px rgba(0,0,0,.25);
            animation: popIn .25s ease;
        }

        body.theme-dark .confirm-modal {
            background: #1e293b;
            color: #e2e8f0;
        }

        @keyframes popIn {

            from {
                opacity: 0;
                transform: scale(.9);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .confirm-modal h4 {
            margin: 0 0 8px;
        }

        .confirm-modal .cm-actions {
            display: flex;
            gap: 10px;
            margin-top: 18px;
        }

        .confirm-modal .cm-actions button {
            flex: 1;
            padding: 10px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
        }

        .cm-yes {
            background: var(--sd-accent2);
            color: #fff;
        }

        .cm-no {
            background: #e2e8f0;
            color: #1e293b;
        }

        /* ====================================================
           CONFETTI
           ==================================================== */

        #confettiCanvas {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 9998;
        }

        /* ====================================================
           SUMMARY MINI BADGE
           ==================================================== */

        .dashboard-summary-note {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: .78rem;
            color: #64748b;
            margin-top: 4px;
        }

        body.theme-dark .dashboard-summary-note {
            color: #94a3b8;
        }

        /* ====================================================
           RESPONSIVE HEADER
           ==================================================== */

        @media (max-width: 900px) {

            .page-header > div {
                width: 100%;
                flex-wrap: wrap;
            }

            .user-badge {
                margin-left: auto;
            }

            .last-updated {
                display: none;
            }
        }

        @media (max-width: 576px) {

            .toast-wrap {
                left: 12px;
                right: 12px;
                top: 12px;
            }

            .toast {
                min-width: 0;
                width: 100%;
            }

            .page-header > div {
                gap: 8px !important;
            }

            .user-badge {
                width: 100%;
                margin-left: 0;
                justify-content: center;
            }
        }

    </style>

</head>

<body>

<canvas id="confettiCanvas"></canvas>

<div
    class="toast-wrap"
    id="toastWrap"
></div>

<!-- ========================================================
     CONFIRMATION MODAL
     ======================================================== -->

<div
    class="confirm-modal-backdrop"
    id="confirmBackdrop"
>

    <div class="confirm-modal">

        <h4>
            <i
                class="fas fa-paper-plane"
                aria-hidden="true"
            ></i>

            Confirm Equipment Issue
        </h4>

        <p id="confirmText">
            Issue this equipment to the selected player?
        </p>

        <div class="cm-actions">

            <button
                class="cm-no"
                type="button"
                id="cmCancel"
            >
                Cancel
            </button>

            <button
                class="cm-yes"
                type="button"
                id="cmConfirm"
            >
                Yes, Issue It
            </button>

        </div>

    </div>

</div>

<!-- ========================================================
     APPLICATION
     ======================================================== -->

<div class="app-container">

    <?php include_once 'sidebar.php'; ?>

    <main class="main-content">

        <!-- ==================================================
             PAGE HEADER
             ================================================== -->

        <div class="page-header">

            <h1>
                <i
                    class="fas fa-chart-pie"
                    aria-hidden="true"
                ></i>

                Dashboard
            </h1>

            <div
                style="
                    display:flex;
                    align-items:center;
                    gap:12px;
                "
            >

                <button
                    class="theme-toggle"
                    id="themeToggle"
                    type="button"
                    title="Toggle light/dark theme"
                >
                    <i
                        class="fas fa-moon"
                        aria-hidden="true"
                    ></i>

                    <span>Dark</span>
                </button>

                <button
                    class="theme-toggle refresh-btn"
                    id="refreshBtn"
                    type="button"
                    title="Refresh dashboard"
                >
                    <i
                        class="fas fa-sync-alt"
                        aria-hidden="true"
                    ></i>

                    <span>Refresh</span>
                </button>

                <span
                    class="last-updated"
                    id="lastUpdated"
                ></span>

                <div class="user-badge">

                    <i
                        class="fas fa-user-circle"
                        aria-hidden="true"
                    ></i>

                    <span>
                        Welcome,
                        <?php
                        echo dashboardEscape(
                            $_SESSION['full_name'] ?? 'Admin'
                        );
                        ?>
                    </span>

                </div>

            </div>

        </div>

        <!-- ==================================================
             WELCOME BANNER
             ================================================== -->

        <div class="welcome-banner">

            <div>

                <h2>

                    <i
                        class="fas fa-sun"
                        aria-hidden="true"
                    ></i>

                    Welcome Back,
                    <?php
                    echo dashboardEscape(
                        $_SESSION['full_name'] ?? 'Admin'
                    );
                    ?>!

                </h2>

                <p>
                    Here's what's happening with your sports
                    program today.
                </p>

                <div class="dashboard-summary-note">

                    <i
                        class="fas fa-database"
                        aria-hidden="true"
                    ></i>

                    Live figures synchronized with the
                    database reporting views.

                </div>

            </div>

            <div class="date-badge">

                <i
                    class="fas fa-calendar-alt"
                    aria-hidden="true"
                ></i>

                <?php echo date('l, F j, Y'); ?>

            </div>

        </div>

        <!-- ==================================================
             ALERTS
             ================================================== -->

        <div class="alerts-grid">

            <?php if ($pendingReturns > 0): ?>

                <div class="alert-card warning">

                    <i
                        class="fas fa-clock alert-icon"
                        aria-hidden="true"
                    ></i>

                    <div style="flex:1;">

                        <p class="alert-title">
                            <?php
                            echo number_format(
                                $pendingReturns
                            );
                            ?>
                            item(s) currently issued
                        </p>

                        <p class="alert-meta">

                            <?php
                            echo number_format(
                                $pendingClearance
                            );
                            ?>
                            player(s) have pending clearance

                        </p>

                    </div>

                    <a
                        href="clearance.php"
                        class="alert-link"
                    >
                        View &rarr;
                    </a>

                </div>

            <?php endif; ?>


            <?php if ($lowStock > 0): ?>

                <div class="alert-card danger">

                    <i
                        class="fas fa-exclamation-triangle alert-icon"
                        aria-hidden="true"
                    ></i>

                    <div style="flex:1;">

                        <p class="alert-title">
                            <?php
                            echo number_format($lowStock);
                            ?>
                            item(s) low in stock
                        </p>

                        <p class="alert-meta">
                            Some inventory items need attention.
                        </p>

                    </div>

                    <a
                        href="reports.php"
                        class="alert-link"
                    >
                        View &rarr;
                    </a>

                </div>

            <?php endif; ?>


            <?php
            if (
                $pendingReturns === 0
                && $lowStock === 0
                && $pendingClearance === 0
            ):
            ?>

                <div class="alert-card success">

                    <i
                        class="fas fa-check-circle alert-icon"
                        aria-hidden="true"
                    ></i>

                    <div style="flex:1;">

                        <p class="alert-title">
                            All Clear!
                        </p>

                        <p class="alert-meta">
                            No outstanding equipment or
                            low-stock warnings.
                        </p>

                    </div>

                </div>

            <?php endif; ?>

        </div>

        <!-- ==================================================
             FLASH DATA
             ================================================== -->

        <div
            role="alert"
            aria-live="polite"
            id="serverFlash"
            data-success="<?php
                echo dashboardEscape(
                    $quick_success ?? ''
                );
            ?>"
            data-error="<?php
                echo dashboardEscape(
                    $quick_error ?? ''
                );
            ?>"
        ></div>

        <!-- ==================================================
             STATISTICS
             ================================================== -->

        <div class="stats-grid">

            <!-- Total Players -->

            <div
                class="stat-card"
                data-count="<?php echo $totalPlayers; ?>"
            >

                <span class="stat-icon-chip">

                    <i
                        class="fas fa-users"
                        aria-hidden="true"
                    ></i>

                </span>

                <span
                    class="stat-number"
                    data-target="<?php echo $totalPlayers; ?>"
                >
                    0
                </span>

                <span class="stat-label">
                    Total Players
                </span>

            </div>


            <!-- Cleared Players -->

            <div
                class="stat-card is-success"
                data-count="<?php echo $clearedPlayers; ?>"
            >

                <span class="stat-icon-chip">

                    <i
                        class="fas fa-check-circle"
                        aria-hidden="true"
                    ></i>

                </span>

                <span
                    class="stat-number"
                    data-target="<?php echo $clearedPlayers; ?>"
                >
                    0
                </span>

                <span class="stat-label">
                    Cleared Players
                </span>

            </div>


            <!-- Pending Clearance -->

            <div
                class="stat-card is-warning"
                data-count="<?php echo $pendingClearance; ?>"
            >

                <span class="stat-icon-chip">

                    <i
                        class="fas fa-clock"
                        aria-hidden="true"
                    ></i>

                </span>

                <span
                    class="stat-number"
                    data-target="<?php echo $pendingClearance; ?>"
                >
                    0
                </span>

                <span class="stat-label">
                    Pending Clearance
                </span>

            </div>


            <!-- Equipment Issued -->

            <div
                class="stat-card"
                data-count="<?php echo $equipmentIssued; ?>"
            >

                <span class="stat-icon-chip">

                    <i
                        class="fas fa-hand-holding-box"
                        aria-hidden="true"
                    ></i>

                </span>

                <span
                    class="stat-number"
                    data-target="<?php echo $equipmentIssued; ?>"
                >
                    0
                </span>

                <span class="stat-label">
                    Equipment Issued
                </span>

            </div>


            <!-- Stock -->

            <div
                class="stat-card"
                data-count="<?php echo $totalStock; ?>"
            >

                <span class="stat-icon-chip">

                    <i
                        class="fas fa-warehouse"
                        aria-hidden="true"
                    ></i>

                </span>

                <span
                    class="stat-number"
                    data-target="<?php echo $totalStock; ?>"
                >
                    0
                </span>

                <span class="stat-label">
                    Items in Stock
                </span>

            </div>


            <!-- Tournaments -->

            <div
                class="stat-card"
                data-count="<?php echo $totalTournaments; ?>"
            >

                <span class="stat-icon-chip">

                    <i
                        class="fas fa-trophy"
                        aria-hidden="true"
                    ></i>

                </span>

                <span
                    class="stat-number"
                    data-target="<?php echo $totalTournaments; ?>"
                >
                    0
                </span>

                <span class="stat-label">
                    Tournaments
                </span>

            </div>

        </div>

        <!-- ==================================================
             CHARTS
             ================================================== -->

        <div class="charts-grid">

            <!-- Players by Team -->

            <div class="chart-card">

                <h3>

                    <i
                        class="fas fa-users"
                        aria-hidden="true"
                    ></i>

                    Players by Team

                </h3>

                <div
                    class="chart-wrapper"
                    id="teamChartWrapper"
                >

                    <?php if ($hasTeamData): ?>

                        <canvas
                            id="teamChart"
                            role="img"
                            aria-label="Doughnut chart of players grouped by team"
                        ></canvas>

                    <?php else: ?>

                        <div class="chart-empty-state">

                            <i
                                class="fas fa-users-slash"
                                aria-hidden="true"
                            ></i>

                            <p>
                                No team data yet.
                            </p>

                        </div>

                    <?php endif; ?>

                </div>

            </div>


            <!-- Monthly Registrations -->

            <div class="chart-card">

                <h3>

                    <i
                        class="fas fa-calendar-alt"
                        aria-hidden="true"
                    ></i>

                    Monthly Registrations

                </h3>

                <div
                    class="chart-wrapper"
                    id="registrationsChartWrapper"
                >

                    <canvas
                        id="registrationsChart"
                        role="img"
                        aria-label="Bar chart of new player registrations for the last six months"
                    ></canvas>

                </div>

            </div>


            <!-- Equipment Status -->

            <div class="chart-card">

                <h3>

                    <i
                        class="fas fa-clipboard-check"
                        aria-hidden="true"
                    ></i>

                    Equipment Status

                </h3>

                <div
                    class="chart-wrapper"
                    id="itemStatusChartWrapper"
                >

                    <?php if ($hasItemStatusData): ?>

                        <canvas
                            id="itemStatusChart"
                            role="img"
                            aria-label="Doughnut chart of equipment status"
                        ></canvas>

                    <?php else: ?>

                        <div class="chart-empty-state">

                            <i
                                class="fas fa-box-open"
                                aria-hidden="true"
                            ></i>

                            <p>
                                No equipment transactions yet.
                            </p>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>

        <!-- ==================================================
             QUICK ACTIONS
             ================================================== -->

        <div class="quick-actions">

            <!-- Quick Issue -->

            <div class="action-card">

                <h3>

                    <i
                        class="fas fa-plus-circle"
                        style="color:var(--color-success);"
                        aria-hidden="true"
                    ></i>

                    Quick Issue Equipment

                </h3>

                <form
                    action="dashboard.php"
                    method="POST"
                    class="quick-issue-form"
                    id="quickIssueForm"
                >

                    <input
                        type="hidden"
                        name="quick_issue"
                        value="1"
                    >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?php
                            echo dashboardEscape(
                                $csrf_token
                            );
                        ?>"
                    >

                    <!-- PLAYER -->

                    <label
                        class="field-label"
                        for="quick-player-search"
                    >
                        Select Player
                    </label>

                    <div class="searchable-select">

                        <input
                            type="text"
                            class="ss-search"
                            id="quick-player-search"
                            placeholder="Type to search players..."
                            autocomplete="off"
                        >

                        <select
                            id="quick-player"
                            name="player_id"
                            required
                        >

                            <option value="">
                                Select Player...
                            </option>

                            <?php
                            foreach (
                                $quickPlayers
                                as $player
                            ):
                            ?>

                                <option
                                    value="<?php
                                        echo (int)$player['id'];
                                    ?>"
                                    data-search="<?php
                                        echo dashboardEscape(
                                            strtolower(
                                                $player['student_id']
                                                . ' '
                                                . $player['full_name']
                                            )
                                        );
                                    ?>"
                                >

                                    <?php
                                    echo dashboardEscape(
                                        $player['student_id']
                                        . ' - '
                                        . $player['full_name']
                                    );
                                    ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                        <p class="ss-hint">

                            <?php
                            echo count($quickPlayers);
                            ?>
                            player(s) loaded

                        </p>

                    </div>


                    <!-- EQUIPMENT -->

                    <label
                        class="field-label"
                        for="quick-item-search"
                    >
                        Select Equipment
                    </label>

                    <div class="searchable-select">

                        <input
                            type="text"
                            class="ss-search"
                            id="quick-item-search"
                            placeholder="Type to search equipment..."
                            autocomplete="off"
                        >

                        <select
                            id="quick-item"
                            name="inventory_id"
                            required
                        >

                            <option value="">
                                Select Equipment...
                            </option>

                            <?php
                            foreach (
                                $availableItems
                                as $item
                            ):
                            ?>

                                <option
                                    value="<?php
                                        echo (int)$item['id'];
                                    ?>"
                                    data-search="<?php
                                        echo dashboardEscape(
                                            strtolower(
                                                $item['asset_number']
                                                . ' '
                                                . $item['item_name']
                                            )
                                        );
                                    ?>"
                                >

                                    <?php
                                    echo dashboardEscape(
                                        $item['asset_number']
                                        . ' - '
                                        . $item['item_name']
                                        . ' ('
                                        . $item['quantity']
                                        . ' available)'
                                    );
                                    ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                        <p class="ss-hint">

                            <?php
                            echo count($availableItems);
                            ?>
                            equipment item(s) available

                        </p>

                    </div>


                    <button
                        type="submit"
                        class="btn btn-success btn-block"
                    >

                        <i
                            class="fas fa-paper-plane"
                            aria-hidden="true"
                        ></i>

                        Issue Equipment

                    </button>

                </form>

                <div class="card-footer">

                    <a
                        href="issue_item.php"
                        class="quick-link"
                    >

                        <i
                            class="fas fa-arrow-right"
                            aria-hidden="true"
                        ></i>

                        Full Issue Page

                    </a>

                </div>

            </div>


            <!-- Quick Actions -->

            <div class="action-card">

                <h3>

                    <i
                        class="fas fa-bolt"
                        style="color:var(--color-warning);"
                        aria-hidden="true"
                    ></i>

                    Quick Actions

                </h3>

                <div
                    style="
                        display:flex;
                        flex-direction:column;
                        gap:10px;
                    "
                >

                    <a
                        href="players.php"
                        class="btn btn-primary btn-block"
                    >

                        <i
                            class="fas fa-user-plus"
                            aria-hidden="true"
                        ></i>

                        Register New Player

                    </a>

                    <a
                        href="clearance.php"
                        class="btn btn-primary alt btn-block"
                    >

                        <i
                            class="fas fa-clipboard-check"
                            aria-hidden="true"
                        ></i>

                        View Clearance

                    </a>

                    <a
                        href="reports.php"
                        class="btn btn-primary confirm btn-block"
                    >

                        <i
                            class="fas fa-chart-bar"
                            aria-hidden="true"
                        ></i>

                        Generate Reports

                    </a>

                </div>

            </div>

        </div>

        <!-- ==================================================
             RECENT ACTIVITIES
             ================================================== -->

        <div class="activities-card">

            <h3>

                <i
                    class="fas fa-history"
                    aria-hidden="true"
                ></i>

                Recent Activities

            </h3>

            <?php if (!empty($activities)): ?>

                <?php
                foreach (
                    $activities
                    as $activity
                ):
                ?>

                    <?php
                    $activityType =
                        $activity['type']
                        ?? 'equipment_issued';

                    $isPlayer =
                        $activityType
                        === 'player_registered';

                    $isReturned =
                        $activityType
                        === 'equipment_returned';

                    $iconClass = $isPlayer
                        ? 'fa-user-plus'
                        : (
                            $isReturned
                            ? 'fa-rotate-left'
                            : 'fa-box'
                        );

                    $activityClass = $isPlayer
                        ? 'player'
                        : 'item';
                    ?>

                    <div class="activity-item">

                        <div
                            class="activity-icon <?php
                                echo dashboardEscape(
                                    $activityClass
                                );
                            ?>"
                        >

                            <i
                                class="fas <?php
                                    echo $iconClass;
                                ?>"
                                aria-hidden="true"
                            ></i>

                        </div>

                        <div class="activity-content">

                            <div class="title">

                                <?php
                                echo dashboardEscape(
                                    $activity['activity']
                                    ?? ''
                                );
                                ?>

                            </div>

                            <div class="meta">

                                <span>

                                    <i
                                        class="fas fa-clock"
                                        aria-hidden="true"
                                    ></i>

                                    <?php

                                    $activityDate =
                                        $activity['activity_date']
                                        ?? null;

                                    if ($activityDate) {

                                        echo dashboardEscape(
                                            date(
                                                'M d, Y H:i',
                                                strtotime(
                                                    $activityDate
                                                )
                                            )
                                        );

                                    } else {

                                        echo 'Unknown date';
                                    }

                                    ?>

                                </span>

                                <?php
                                if (
                                    !empty(
                                        $activity['activity_user']
                                    )
                                ):
                                ?>

                                    <span>

                                        <i
                                            class="fas fa-user"
                                            aria-hidden="true"
                                        ></i>

                                        <?php
                                        echo dashboardEscape(
                                            $activity[
                                                'activity_user'
                                            ]
                                        );
                                        ?>

                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="empty-state">

                    <i
                        class="fas fa-inbox"
                        aria-hidden="true"
                    ></i>

                    <p>
                        No recent activities found.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </main>

</div>

<!-- ========================================================
     JAVASCRIPT
     ======================================================== -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const colors = [
            '#1d6fa5',
            '#16a34a',
            '#b45309',
            '#c0311f',
            '#7c5cbf',
            '#0d9488',
            '#c2437a',
            '#2563eb',
            '#0891b2',
            '#9333ea'
        ];

        /* ====================================================
           CHART LOADING
           ==================================================== */

        function markLoaded(wrapperId) {

            const element =
                document.getElementById(wrapperId);

            if (element) {
                element.classList.add('is-loaded');
            }
        }

        /* ====================================================
           THEME
           ==================================================== */

        const themeToggle =
            document.getElementById('themeToggle');

        function applyTheme(dark) {

            document.body.classList.toggle(
                'theme-dark',
                dark
            );

            if (!themeToggle) {
                return;
            }

            themeToggle.innerHTML = dark
                ? '<i class="fas fa-sun" aria-hidden="true"></i> <span>Light</span>'
                : '<i class="fas fa-moon" aria-hidden="true"></i> <span>Dark</span>';
        }

        applyTheme(
            localStorage.getItem('sd_theme') === 'dark'
        );

        if (themeToggle) {

            themeToggle.addEventListener(
                'click',
                function () {

                    const dark =
                        !document.body.classList.contains(
                            'theme-dark'
                        );

                    applyTheme(dark);

                    localStorage.setItem(
                        'sd_theme',
                        dark ? 'dark' : 'light'
                    );
                }
            );
        }

        /* ====================================================
           REFRESH
           ==================================================== */

        const refreshBtn =
            document.getElementById('refreshBtn');

        const lastUpdated =
            document.getElementById('lastUpdated');

        function stampUpdated() {

            if (!lastUpdated) {
                return;
            }

            lastUpdated.textContent =
                'Updated ' +
                new Date().toLocaleTimeString();
        }

        stampUpdated();

        if (refreshBtn) {

            refreshBtn.addEventListener(
                'click',
                function () {

                    refreshBtn.classList.add(
                        'spinning'
                    );

                    setTimeout(
                        function () {
                            window.location.reload();
                        },
                        350
                    );
                }
            );
        }

        /* ====================================================
           TOASTS
           ==================================================== */

        const flash =
            document.getElementById('serverFlash');

        const toastWrap =
            document.getElementById('toastWrap');

        function showToast(
            message,
            type
        ) {

            if (!message || !toastWrap) {
                return;
            }

            const toast =
                document.createElement('div');

            toast.className =
                'toast ' + type;

            const icon =
                type === 'success'
                ? 'fa-check-circle'
                : 'fa-exclamation-circle';

            toast.innerHTML =
                '<i class="fas '
                + icon
                + '" aria-hidden="true"></i>'
                + '<span></span>';

            toast.querySelector('span')
                .textContent = message;

            toastWrap.appendChild(toast);

            setTimeout(
                function () {
                    toast.remove();
                },
                4200
            );

            if (type === 'success') {
                launchConfetti();
            }
        }

        if (flash) {

            showToast(
                flash.dataset.success,
                'success'
            );

            showToast(
                flash.dataset.error,
                'error'
            );
        }

        /* ====================================================
           ANIMATED STAT COUNTERS
           ==================================================== */

        document
            .querySelectorAll(
                '.stat-number[data-target]'
            )
            .forEach(
                function (element) {

                    const target =
                        parseInt(
                            element.dataset.target,
                            10
                        ) || 0;

                    let current = 0;

                    if (target === 0) {
                        element.textContent = '0';
                        return;
                    }

                    const step =
                        Math.max(
                            1,
                            Math.ceil(
                                target / 40
                            )
                        );

                    const timer =
                        setInterval(
                            function () {

                                current += step;

                                if (
                                    current >= target
                                ) {

                                    current =
                                        target;

                                    clearInterval(
                                        timer
                                    );
                                }

                                element.textContent =
                                    current.toLocaleString();

                            },
                            20
                        );
                }
            );

        /* ====================================================
           SEARCHABLE SELECTS
           ==================================================== */

        function wireSearch(
            inputId,
            selectId
        ) {

            const input =
                document.getElementById(
                    inputId
                );

            const select =
                document.getElementById(
                    selectId
                );

            if (!input || !select) {
                return;
            }

            const options =
                Array.from(
                    select.options
                ).filter(
                    function (option) {
                        return option.value !== '';
                    }
                );

            input.addEventListener(
                'input',
                function () {

                    const query =
                        input.value
                            .trim()
                            .toLowerCase();

                    options.forEach(
                        function (option) {

                            const searchable =
                                (
                                    option.dataset.search
                                    || option.textContent
                                ).toLowerCase();

                            option.hidden =
                                query !== ''
                                && !searchable.includes(
                                    query
                                );
                        }
                    );
                }
            );

            input.addEventListener(
                'focus',
                function () {

                    if (
                        input.value.trim() === ''
                    ) {

                        options.forEach(
                            function (option) {
                                option.hidden =
                                    false;
                            }
                        );
                    }
                }
            );
        }

        wireSearch(
            'quick-player-search',
            'quick-player'
        );

        wireSearch(
            'quick-item-search',
            'quick-item'
        );

        /* ====================================================
           CONFIRMATION MODAL
           ==================================================== */

        const form =
            document.getElementById(
                'quickIssueForm'
            );

        const backdrop =
            document.getElementById(
                'confirmBackdrop'
            );

        const confirmText =
            document.getElementById(
                'confirmText'
            );

        const cmYes =
            document.getElementById(
                'cmConfirm'
            );

        const cmNo =
            document.getElementById(
                'cmCancel'
            );

        if (
            form
            && backdrop
            && confirmText
            && cmYes
            && cmNo
        ) {

            form.addEventListener(
                'submit',
                function (event) {

                    event.preventDefault();

                    const playerSelect =
                        document.getElementById(
                            'quick-player'
                        );

                    const itemSelect =
                        document.getElementById(
                            'quick-item'
                        );

                    if (
                        !playerSelect.value
                        || !itemSelect.value
                    ) {

                        showToast(
                            'Please select both a player and equipment item.',
                            'error'
                        );

                        return;
                    }

                    const playerLabel =
                        playerSelect
                            .options[
                                playerSelect.selectedIndex
                            ]
                            .textContent
                            .trim();

                    const itemLabel =
                        itemSelect
                            .options[
                                itemSelect.selectedIndex
                            ]
                            .textContent
                            .trim();

                    confirmText.textContent =
                        'Issue "'
                        + itemLabel
                        + '" to '
                        + playerLabel
                        + '?';

                    backdrop.classList.add(
                        'open'
                    );
                }
            );

            cmNo.addEventListener(
                'click',
                function () {

                    backdrop.classList.remove(
                        'open'
                    );
                }
            );

            backdrop.addEventListener(
                'click',
                function (event) {

                    if (
                        event.target === backdrop
                    ) {

                        backdrop.classList.remove(
                            'open'
                        );
                    }
                }
            );

            cmYes.addEventListener(
                'click',
                function () {

                    backdrop.classList.remove(
                        'open'
                    );

                    form.submit();
                }
            );

            document.addEventListener(
                'keydown',
                function (event) {

                    if (
                        event.key === 'Escape'
                        && backdrop.classList.contains(
                            'open'
                        )
                    ) {

                        backdrop.classList.remove(
                            'open'
                        );
                    }
                }
            );
        }

        /* ====================================================
           CONFETTI
           ==================================================== */

        function launchConfetti() {

            const canvas =
                document.getElementById(
                    'confettiCanvas'
                );

            if (!canvas) {
                return;
            }

            canvas.width =
                window.innerWidth;

            canvas.height =
                window.innerHeight;

            const ctx =
                canvas.getContext('2d');

            if (!ctx) {
                return;
            }

            const pieces =
                Array.from(
                    { length: 80 },
                    function () {

                        return {

                            x:
                                Math.random()
                                * canvas.width,

                            y:
                                -20
                                -
                                Math.random()
                                * canvas.height
                                * .3,

                            r:
                                4
                                +
                                Math.random()
                                * 5,

                            c:
                                colors[
                                    Math.floor(
                                        Math.random()
                                        * colors.length
                                    )
                                ],

                            vy:
                                2
                                +
                                Math.random()
                                * 3,

                            vx:
                                -1.5
                                +
                                Math.random()
                                * 3,

                            rot:
                                Math.random()
                                * 360
                        };
                    }
                );

            let frame = 0;

            function draw() {

                ctx.clearRect(
                    0,
                    0,
                    canvas.width,
                    canvas.height
                );

                pieces.forEach(
                    function (piece) {

                        piece.y += piece.vy;
                        piece.x += piece.vx;
                        piece.rot += 6;

                        ctx.save();

                        ctx.translate(
                            piece.x,
                            piece.y
                        );

                        ctx.rotate(
                            (
                                piece.rot
                                * Math.PI
                            ) / 180
                        );

                        ctx.fillStyle =
                            piece.c;

                        ctx.fillRect(
                            -piece.r / 2,
                            -piece.r / 2,
                            piece.r,
                            piece.r
                        );

                        ctx.restore();
                    }
                );

                frame++;

                if (frame < 90) {

                    requestAnimationFrame(
                        draw
                    );

                } else {

                    ctx.clearRect(
                        0,
                        0,
                        canvas.width,
                        canvas.height
                    );
                }
            }

            draw();
        }

        /* ====================================================
           TEAM CHART
           ==================================================== */

        <?php if ($hasTeamData): ?>

        const teamCanvas =
            document.getElementById(
                'teamChart'
            );

        if (teamCanvas) {

            new Chart(
                teamCanvas,
                {
                    type: 'doughnut',

                    data: {

                        labels:
                            <?php
                            echo json_encode(
                                $teamLabels,
                                JSON_UNESCAPED_UNICODE
                            );
                            ?>,

                        datasets: [{

                            data:
                                <?php
                                echo json_encode(
                                    $teamCounts
                                );
                                ?>,

                            backgroundColor:
                                colors.slice(
                                    0,
                                    <?php
                                    echo count(
                                        $teamLabels
                                    );
                                    ?>
                                ),

                            borderWidth: 2,

                            borderColor: '#fff'
                        }]
                    },

                    options: {

                        responsive: true,

                        maintainAspectRatio:
                            false,

                        animation: {
                            animateScale: true,
                            duration: 900
                        },

                        plugins: {

                            legend: {
                                position: 'bottom',

                                labels: {
                                    boxWidth: 12,
                                    padding: 8,
                                    font: {
                                        size: 10
                                    }
                                }
                            }
                        },

                        cutout: '65%'
                    }
                }
            );

            markLoaded(
                'teamChartWrapper'
            );
        }

        <?php endif; ?>


        /* ====================================================
           REGISTRATION CHART
           ==================================================== */

        const registrationsCanvas =
            document.getElementById(
                'registrationsChart'
            );

        if (registrationsCanvas) {

            new Chart(
                registrationsCanvas,
                {
                    type: 'bar',

                    data: {

                        labels:
                            <?php
                            echo json_encode(
                                $monthLabels
                            );
                            ?>,

                        datasets: [{

                            label:
                                'New Players',

                            data:
                                <?php
                                echo json_encode(
                                    $monthCounts
                                );
                                ?>,

                            backgroundColor:
                                'rgba(29, 111, 165, 0.7)',

                            hoverBackgroundColor:
                                '#1d6fa5',

                            borderColor:
                                '#1d6fa5',

                            borderWidth: 2,

                            borderRadius: 6
                        }]
                    },

                    options: {

                        responsive: true,

                        maintainAspectRatio:
                            false,

                        animation: {
                            duration: 900,
                            easing: 'easeOutQuart'
                        },

                        plugins: {
                            legend: {
                                display: false
                            }
                        },

                        scales: {

                            y: {

                                beginAtZero: true,

                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                }
            );

            markLoaded(
                'registrationsChartWrapper'
            );
        }


        /* ====================================================
           EQUIPMENT STATUS CHART
           ==================================================== */

        <?php if ($hasItemStatusData): ?>

        const itemStatusCanvas =
            document.getElementById(
                'itemStatusChart'
            );

        if (itemStatusCanvas) {

            new Chart(
                itemStatusCanvas,
                {
                    type: 'doughnut',

                    data: {

                        labels: [
                            'Issued',
                            'Returned',
                            'Lost',
                            'Damaged'
                        ],

                        datasets: [{

                            data: [
                                <?php
                                echo $issuedStatus;
                                ?>,

                                <?php
                                echo $returnedStatus;
                                ?>,

                                <?php
                                echo $lostStatus;
                                ?>,

                                <?php
                                echo $damagedStatus;
                                ?>
                            ],

                            backgroundColor: [
                                '#b45309',
                                '#16a34a',
                                '#c0311f',
                                '#7c5cbf'
                            ],

                            borderWidth: 2,

                            borderColor: '#fff'
                        }]
                    },

                    options: {

                        responsive: true,

                        maintainAspectRatio:
                            false,

                        animation: {
                            animateScale: true,
                            duration: 900
                        },

                        plugins: {

                            legend: {
                                position: 'bottom',

                                labels: {
                                    boxWidth: 12,
                                    padding: 8,
                                    font: {
                                        size: 10
                                    }
                                }
                            }
                        },

                        cutout: '65%'
                    }
                }
            );

            markLoaded(
                'itemStatusChartWrapper'
            );
        }

        <?php endif; ?>

    }
);

</script>

</body>
</html>
