<?php
/**
 * ============================================================
 * MACHAKOS UNIVERSITY SPORTS MANAGEMENT SYSTEM V2
 * File: admin/issue_item.php
 * Module: Equipment Issuing
 * Database: machakos_sports_v2
 * ============================================================
 *
 * PURPOSE:
 * Issue sports equipment to registered players.
 *
 * VERIFIED V2 TABLES USED:
 *
 * players
 * inventory_items
 * equipment_issues
 * player_clearance
 * activity_log
 *
 * IMPORTANT:
 * This file uses the actual V2 database schema.
 * ============================================================
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

/* ============================================================
   SESSION / CSRF
   ============================================================ */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(
        random_bytes(32)
    );
}

$csrfToken = $_SESSION['csrf_token'];


/* ============================================================
   INITIAL STATE
   ============================================================ */

$success = null;
$error = null;

$players = [];
$items = [];
$recentIssues = [];

$selectedPlayerId = '';
$selectedInventoryId = '';
$issueQuantity = 1;
$expectedReturnDate = '';
$remarks = '';


/* ============================================================
   CURRENT USER
   ============================================================ */

$currentUserId = getCurrentUserId();

if (!$currentUserId) {
    $error = 'Your session has expired. Please log in again.';
}


/* ============================================================
   HANDLE FORM SUBMISSION
   ============================================================ */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && $currentUserId
) {

    /* --------------------------------------------------------
       CSRF VALIDATION
       -------------------------------------------------------- */

    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        empty($submittedToken)
        || !hash_equals(
            $_SESSION['csrf_token'],
            $submittedToken
        )
    ) {
        $error = 'Security validation failed. Please refresh the page and try again.';
    }

    /* --------------------------------------------------------
       READ FORM VALUES
       -------------------------------------------------------- */

    $selectedPlayerId = safeInt(
        $_POST['player_id'] ?? 0
    );

    $selectedInventoryId = safeInt(
        $_POST['inventory_id'] ?? 0
    );

    $issueQuantity = safeInt(
        $_POST['quantity'] ?? 1
    );

    $expectedReturnDate =
        trim($_POST['expected_return_date'] ?? '');

    $remarks =
        trim($_POST['remarks'] ?? '');


    /* --------------------------------------------------------
       BASIC VALIDATION
       -------------------------------------------------------- */

    if (!$error) {

        if ($selectedPlayerId <= 0) {
            $error = 'Please select a valid player.';
        }

        elseif ($selectedInventoryId <= 0) {
            $error = 'Please select a valid inventory item.';
        }

        elseif ($issueQuantity <= 0) {
            $error = 'Issue quantity must be at least 1.';
        }

        elseif (
            !empty($expectedReturnDate)
            && !preg_match(
                '/^\d{4}-\d{2}-\d{2}$/',
                $expectedReturnDate
            )
        ) {
            $error = 'Please provide a valid expected return date.';
        }
    }


    /* --------------------------------------------------------
       TRANSACTION
       -------------------------------------------------------- */

    if (!$error) {

        try {

            $pdo->beginTransaction();


            /* =================================================
               1. VERIFY PLAYER
               ================================================= */

            $playerStmt = $pdo->prepare("
                SELECT
                    id,
                    student_id,
                    full_name,
                    team,
                    status
                FROM players
                WHERE id = :player_id
                LIMIT 1
            ");

            $playerStmt->execute([
                ':player_id' => $selectedPlayerId
            ]);

            $player = $playerStmt->fetch(
                PDO::FETCH_ASSOC
            );

            if (!$player) {
                throw new RuntimeException(
                    'The selected player could not be found.'
                );
            }

            if ($player['status'] !== 'Active') {
                throw new RuntimeException(
                    'Equipment can only be issued to an active player.'
                );
            }


            /* =================================================
               2. LOCK INVENTORY ROW
               ================================================= */

            $inventoryStmt = $pdo->prepare("
                SELECT
                    id,
                    category_id,
                    supplier_id,
                    asset_number,
                    barcode,
                    serial_number,
                    item_name,
                    description,
                    brand,
                    model,
                    quantity,
                    minimum_stock,
                    unit,
                    item_condition,
                    status,
                    storage_location
                FROM inventory_items
                WHERE id = :inventory_id
                LIMIT 1
                FOR UPDATE
            ");

            $inventoryStmt->execute([
                ':inventory_id' => $selectedInventoryId
            ]);

            $inventory = $inventoryStmt->fetch(
                PDO::FETCH_ASSOC
            );

            if (!$inventory) {
                throw new RuntimeException(
                    'The selected inventory item could not be found.'
                );
            }


            /* =================================================
               3. VERIFY ITEM STATUS
               ================================================= */

            if (
                in_array(
                    $inventory['status'],
                    ['Disposed', 'Lost', 'Maintenance'],
                    true
                )
            ) {
                throw new RuntimeException(
                    'This item is currently not available for issuing.'
                );
            }


            /* =================================================
               4. VERIFY STOCK
               ================================================= */

            $availableQuantity = (int)$inventory['quantity'];

            if ($availableQuantity <= 0) {
                throw new RuntimeException(
                    'This item is out of stock.'
                );
            }

            if ($issueQuantity > $availableQuantity) {
                throw new RuntimeException(
                    'Only ' .
                    $availableQuantity .
                    ' ' .
                    ($inventory['unit'] ?: 'unit(s)') .
                    ' of this item are available.'
                );
            }


            /* =================================================
               5. INSERT EQUIPMENT ISSUE
               ================================================= */

            $issueStmt = $pdo->prepare("
                INSERT INTO equipment_issues
                (
                    player_id,
                    inventory_id,
                    quantity,
                    issue_date,
                    expected_return_date,
                    issued_by,
                    status,
                    remarks
                )
                VALUES
                (
                    :player_id,
                    :inventory_id,
                    :quantity,
                    CURRENT_TIMESTAMP,
                    :expected_return_date,
                    :issued_by,
                    'Issued',
                    :remarks
                )
            ");

            $issueStmt->execute([
                ':player_id' =>
                    $selectedPlayerId,

                ':inventory_id' =>
                    $selectedInventoryId,

                ':quantity' =>
                    $issueQuantity,

                ':expected_return_date' =>
                    !empty($expectedReturnDate)
                    ? $expectedReturnDate
                    : null,

                ':issued_by' =>
                    $currentUserId,

                ':remarks' =>
                    !empty($remarks)
                    ? $remarks
                    : null
            ]);

            $issueId = (int)$pdo->lastInsertId();


            /* =================================================
               6. UPDATE INVENTORY QUANTITY
               ================================================= */

            $newQuantity =
                $availableQuantity -
                $issueQuantity;


            /*
             * If there is still stock available,
             * keep status as Available.
             *
             * If all stock has been issued,
             * mark the inventory item as Issued.
             */
            $newStatus =
                $newQuantity > 0
                ? 'Available'
                : 'Issued';


            $inventoryUpdateStmt = $pdo->prepare("
                UPDATE inventory_items
                SET
                    quantity = :quantity,
                    status = :status,
                    updated_by = :updated_by,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :inventory_id
            ");

            $inventoryUpdateStmt->execute([
                ':quantity' =>
                    $newQuantity,

                ':status' =>
                    $newStatus,

                ':updated_by' =>
                    $currentUserId,

                ':inventory_id' =>
                    $selectedInventoryId
            ]);


            /* =================================================
               7. UPDATE PLAYER CLEARANCE
               ================================================= */

            /*
             * Recalculate the player's clearance immediately.
             *
             * Because the issue is now outstanding,
             * the player's clearance should normally become
             * Pending.
             */
            updatePlayerClearance(
                $pdo,
                $selectedPlayerId
            );


            /* =================================================
               8. ACTIVITY LOG
               ================================================= */

            $activityDescription =
                'Issued ' .
                $issueQuantity .
                ' ' .
                ($inventory['unit'] ?: 'unit(s)') .
                ' of "' .
                $inventory['item_name'] .
                '" to ' .
                $player['full_name'] .
                ' (' .
                $player['student_id'] .
                '). Issue ID: ' .
                $issueId .
                '. Asset: ' .
                $inventory['asset_number'] .
                '.';


            logActivity(
                $pdo,
                'equipment_issued',
                $activityDescription,
                $currentUserId
            );


            /* =================================================
               9. COMMIT
               ================================================= */

            $pdo->commit();


            /* =================================================
               10. SUCCESS MESSAGE
               ================================================= */

            $success =
                'Equipment issued successfully to ' .
                $player['full_name'] .
                '.';

            /*
             * Reset form values after successful submission.
             */
            $selectedPlayerId = '';
            $selectedInventoryId = '';
            $issueQuantity = 1;
            $expectedReturnDate = '';
            $remarks = '';


        } catch (Throwable $e) {

            /*
             * Roll back the entire transaction.
             */
            if (
                $pdo->inTransaction()
            ) {
                $pdo->rollBack();
            }

            error_log(
                'Equipment issuing error: ' .
                $e->getMessage()
            );

            $error =
                $e instanceof RuntimeException
                ? $e->getMessage()
                : 'The equipment could not be issued. Please try again.';
        }
    }
}


/* ============================================================
   LOAD ACTIVE PLAYERS
   ============================================================ */

try {

    $stmt = $pdo->query("
        SELECT
            id,
            student_id,
            registration_no,
            full_name,
            gender,
            team,
            faculty,
            department,
            course,
            year_of_study,
            status
        FROM players
        WHERE status = 'Active'
        ORDER BY full_name ASC
    ");

    $players = $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );

} catch (PDOException $e) {

    $players = [];

    if (!$error) {
        $error =
            'Could not load players. Please try again.';
    }

    error_log(
        'Issue item player query error: ' .
        $e->getMessage()
    );
}


/* ============================================================
   LOAD AVAILABLE INVENTORY
   ============================================================ */

try {

    $stmt = $pdo->query("
        SELECT
            ii.id,
            ii.category_id,
            ii.asset_number,
            ii.barcode,
            ii.serial_number,
            ii.item_name,
            ii.brand,
            ii.model,
            ii.quantity,
            ii.minimum_stock,
            ii.unit,
            ii.item_condition,
            ii.status,
            ii.storage_location,
            c.category_name
        FROM inventory_items ii
        LEFT JOIN equipment_categories c
            ON c.id = ii.category_id
        WHERE ii.quantity > 0
          AND ii.status NOT IN (
              'Disposed',
              'Lost',
              'Maintenance'
          )
        ORDER BY
            ii.item_name ASC,
            ii.asset_number ASC
    ");

    $items = $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );

} catch (PDOException $e) {

    $items = [];

    if (!$error) {
        $error =
            'Could not load inventory items. Please try again.';
    }

    error_log(
        'Issue item inventory query error: ' .
        $e->getMessage()
    );
}


/* ============================================================
   LOAD RECENT EQUIPMENT ISSUES
   ============================================================ */

try {

    $stmt = $pdo->query("
        SELECT
            ei.id,
            ei.player_id,
            ei.inventory_id,
            ei.quantity,
            ei.issue_date,
            ei.expected_return_date,
            ei.issued_by,
            ei.status,
            ei.remarks,

            p.student_id,
            p.full_name AS player_name,
            p.team,

            ii.asset_number,
            ii.item_name,
            ii.brand,
            ii.model,
            ii.unit,

            u.full_name AS issued_by_name

        FROM equipment_issues ei

        INNER JOIN players p
            ON p.id = ei.player_id

        INNER JOIN inventory_items ii
            ON ii.id = ei.inventory_id

        LEFT JOIN users u
            ON u.id = ei.issued_by

        ORDER BY
            ei.issue_date DESC,
            ei.id DESC

        LIMIT 15
    ");

    $recentIssues = $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );

} catch (PDOException $e) {

    $recentIssues = [];

    error_log(
        'Recent equipment issues query error: ' .
        $e->getMessage()
    );
}


/* ============================================================
   PREPARE JAVASCRIPT DATA
   ============================================================ */

$playerData = [];

foreach ($players as $player) {

    $playerData[$player['id']] = [
        'student_id' =>
            $player['student_id'],

        'full_name' =>
            $player['full_name'],

        'team' =>
            $player['team'],

        'faculty' =>
            $player['faculty'],

        'course' =>
            $player['course'],

        'year_of_study' =>
            $player['year_of_study']
    ];
}


$itemData = [];

foreach ($items as $item) {

    $itemData[$item['id']] = [
        'item_name' =>
            $item['item_name'],

        'asset_number' =>
            $item['asset_number'],

        'brand' =>
            $item['brand'],

        'model' =>
            $item['model'],

        'quantity' =>
            (int)$item['quantity'],

        'unit' =>
            $item['unit'],

        'condition' =>
            $item['item_condition'],

        'category' =>
            $item['category_name'] ?? ''
    ];
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
        Issue Equipment | Machakos University Sports Management
    </title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        /* ====================================================
           PAGE-SPECIFIC STYLES
           ==================================================== */

        .issue-page {
            max-width: 1500px;
            margin: 0 auto;
        }

        .page-intro {
            margin-bottom: 25px;
        }

        .page-intro h1 {
            margin-bottom: 6px;
        }

        .page-intro p {
            color: #7f8c8d;
            margin: 0;
        }

        .issue-grid {
            display: grid;
            grid-template-columns:
                minmax(320px, 0.9fr)
                minmax(500px, 1.5fr);
            gap: 25px;
            align-items: start;
        }

        .card {
            background: #ffffff;
            border-radius: 16px;
            padding: 25px;
            box-shadow:
                0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid #eef0f2;
            margin-bottom: 25px;
        }

        .card-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: 700;
            color: #1a5276;
            margin-bottom: 8px;
        }

        .card-title i {
            color: #f39c12;
        }

        .card-subtitle {
            display: block;
            color: #7f8c8d;
            font-size: 13px;
            margin-bottom: 22px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
            font-size: 13px;
            color: #34495e;
        }

        .form-group label i {
            color: #f39c12;
            width: 18px;
        }

        .form-control,
        .form-group select,
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #dfe4ea;
            border-radius: 10px;
            background: #fafbfc;
            color: #2c3e50;
            font-size: 14px;
            transition: 0.2s ease;
            font-family: inherit;
        }

        .form-control:focus,
        .form-group select:focus,
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #f39c12;
            background: #ffffff;
            box-shadow:
                0 0 0 4px rgba(243,156,18,0.08);
        }

        .form-group textarea {
            min-height: 90px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .preview-box {
            background: #f8fafc;
            border: 1px solid #e8edf2;
            border-left: 4px solid #f39c12;
            border-radius: 10px;
            padding: 15px 17px;
            margin: -5px 0 18px;
            display: none;
        }

        .preview-box.show {
            display: block;
            animation: fadeIn 0.25s ease;
        }

        .preview-title {
            color: #1a5276;
            font-weight: 700;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .preview-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 4px 0;
            font-size: 13px;
        }

        .preview-row span:first-child {
            color: #7f8c8d;
        }

        .preview-row span:last-child {
            color: #2c3e50;
            font-weight: 600;
            text-align: right;
        }

        .stock-indicator {
            margin-top: 7px;
            font-size: 12px;
            color: #7f8c8d;
        }

        .stock-indicator strong {
            color: #27ae60;
        }

        .stock-indicator.warning strong {
            color: #e67e22;
        }

        .btn-issue {
            width: 100%;
            border: none;
            border-radius: 10px;
            padding: 14px 20px;
            background:
                linear-gradient(
                    135deg,
                    #27ae60,
                    #2ecc71
                );
            color: white;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.25s ease;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 9px;
        }

        .btn-issue:hover {
            transform: translateY(-2px);
            box-shadow:
                0 8px 25px rgba(39,174,96,0.25);
        }

        .btn-issue:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 14px;
        }

        .alert i {
            margin-top: 2px;
        }

        .alert-success {
            background: #eaf8ef;
            border: 1px solid #c9efd5;
            color: #18733b;
        }

        .alert-danger {
            background: #fff0f0;
            border: 1px solid #f3cccc;
            color: #a32929;
        }

        .alert-info {
            background: #eef7fc;
            border: 1px solid #cfe8f5;
            color: #17617b;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .data-table th {
            padding: 12px 14px;
            text-align: left;
            background: #f7f9fb;
            color: #52616b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .4px;
            border-bottom: 1px solid #e8edf2;
            white-space: nowrap;
        }

        .data-table td {
            padding: 13px 14px;
            border-bottom: 1px solid #eef1f4;
            color: #34495e;
            vertical-align: middle;
        }

        .data-table tbody tr:hover td {
            background: #fafcfd;
        }

        .player-name {
            font-weight: 700;
            color: #1a5276;
        }

        .secondary-text {
            display: block;
            color: #95a5a6;
            font-size: 11px;
            margin-top: 3px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge-issued {
            background: #fff4df;
            color: #9a6700;
        }

        .badge-overdue {
            background: #ffe8e8;
            color: #a32929;
        }

        .badge-returned {
            background: #e8f8ee;
            color: #18733b;
        }

        .badge-damaged {
            background: #fff0e0;
            color: #a65b00;
        }

        .badge-lost {
            background: #f1e8ff;
            color: #6539a5;
        }

        .empty-state {
            text-align: center;
            padding: 45px 20px;
            color: #95a5a6;
        }

        .empty-state i {
            display: block;
            font-size: 45px;
            color: #dfe4e8;
            margin-bottom: 12px;
        }

        .form-note {
            margin-top: 5px;
            color: #95a5a6;
            font-size: 11px;
        }

        .required {
            color: #e74c3c;
        }

        .security-note {
            margin-top: 15px;
            padding: 10px 12px;
            border-radius: 8px;
            background: #f8f9fa;
            color: #7f8c8d;
            font-size: 11px;
        }

        .security-note i {
            color: #27ae60;
            margin-right: 5px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 1100px) {

            .issue-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 650px) {

            .form-row {
                grid-template-columns: 1fr;
            }

            .card {
                padding: 18px;
            }

            .preview-row {
                flex-direction: column;
                gap: 2px;
            }

            .preview-row span:last-child {
                text-align: left;
            }
        }

    </style>

</head>

<body>

<div class="app-container">

    <?php
    /*
     * Use the existing V2 sidebar when available.
     */
    $sidebarFile = __DIR__ . '/sidebar.php';

    if (file_exists($sidebarFile)) {
        require $sidebarFile;
    }
    ?>

    <main class="main-content">

        <div class="issue-page">

            <!-- =================================================
                 PAGE HEADER
                 ================================================= -->

            <div class="page-header">

                <div class="page-intro">

                    <h1>
                        <i class="fas fa-box-open"></i>
                        Issue Equipment
                    </h1>

                    <p>
                        Issue sports equipment to an active
                        Machakos University player.
                    </p>

                </div>

                <div class="user-badge">

                    <i class="fas fa-user-circle"></i>

                    <span>
                        Welcome,
                        <?php
                        echo e(
                            getCurrentUserName()
                        );
                        ?>
                    </span>

                </div>

            </div>


            <!-- =================================================
                 ALERTS
                 ================================================= -->

            <?php if ($success): ?>

                <div class="alert alert-success">

                    <i class="fas fa-check-circle"></i>

                    <div>
                        <?php echo e($success); ?>
                    </div>

                </div>

            <?php endif; ?>


            <?php if ($error): ?>

                <div class="alert alert-danger">

                    <i class="fas fa-exclamation-circle"></i>

                    <div>
                        <?php echo e($error); ?>
                    </div>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 MAIN GRID
                 ================================================= -->

            <div class="issue-grid">


                <!-- =============================================
                     ISSUE FORM
                     ============================================= -->

                <div class="card">

                    <div class="card-title">

                        <i class="fas fa-paper-plane"></i>

                        Issue Equipment

                    </div>

                    <span class="card-subtitle">

                        Select the player, equipment and
                        quantity to issue.

                    </span>


                    <?php if (empty($players)): ?>

                        <div class="alert alert-info">

                            <i class="fas fa-info-circle"></i>

                            <div>

                                No active players are currently
                                available.

                                <a href="players.php">
                                    Register or activate a player
                                </a>
                                first.

                            </div>

                        </div>

                    <?php elseif (empty($items)): ?>

                        <div class="alert alert-info">

                            <i class="fas fa-box-open"></i>

                            <div>

                                There are currently no equipment
                                items available for issuing.

                                Add stock through the inventory
                                management module first.

                            </div>

                        </div>

                    <?php else: ?>


                        <form
                            method="POST"
                            action="issue_item.php"
                            id="issueForm"
                            autocomplete="off"
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?php echo e($csrfToken); ?>"
                            >


                            <!-- PLAYER -->

                            <div class="form-group">

                                <label for="player_id">

                                    <i class="fas fa-user"></i>

                                    Player
                                    <span class="required">*</span>

                                </label>

                                <select
                                    id="player_id"
                                    name="player_id"
                                    required
                                >

                                    <option value="">
                                        -- Select Active Player --
                                    </option>

                                    <?php foreach (
                                        $players
                                        as $player
                                    ): ?>

                                        <option
                                            value="<?php
                                                echo (int)$player['id'];
                                            ?>"
                                            <?php
                                            echo (
                                                (int)$selectedPlayerId
                                                ===
                                                (int)$player['id']
                                            )
                                                ? 'selected'
                                                : '';
                                            ?>
                                        >

                                            <?php
                                            echo e(
                                                $player['student_id']
                                                .
                                                ' — '
                                                .
                                                $player['full_name']
                                                .
                                                ' — '
                                                .
                                                $player['team']
                                            );
                                            ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>


                            <!-- PLAYER PREVIEW -->

                            <div
                                id="playerPreview"
                                class="preview-box"
                            >

                                <div class="preview-title">

                                    <i class="fas fa-id-card"></i>

                                    Player Information

                                </div>

                                <div class="preview-row">

                                    <span>
                                        Student ID
                                    </span>

                                    <span id="previewStudentId">
                                        —
                                    </span>

                                </div>

                                <div class="preview-row">

                                    <span>
                                        Name
                                    </span>

                                    <span id="previewPlayerName">
                                        —
                                    </span>

                                </div>

                                <div class="preview-row">

                                    <span>
                                        Team
                                    </span>

                                    <span id="previewTeam">
                                        —
                                    </span>

                                </div>

                                <div class="preview-row">

                                    <span>
                                        Faculty
                                    </span>

                                    <span id="previewFaculty">
                                        —
                                    </span>

                                </div>

                                <div class="preview-row">

                                    <span>
                                        Course
                                    </span>

                                    <span id="previewCourse">
                                        —
                                    </span>

                                </div>

                            </div>


                            <!-- INVENTORY ITEM -->

                            <div class="form-group">

                                <label for="inventory_id">

                                    <i class="fas fa-box"></i>

                                    Equipment
                                    <span class="required">*</span>

                                </label>

                                <select
                                    id="inventory_id"
                                    name="inventory_id"
                                    required
                                >

                                    <option value="">
                                        -- Select Equipment --
                                    </option>

                                    <?php foreach (
                                        $items
                                        as $item
                                    ): ?>

                                        <option
                                            value="<?php
                                                echo (int)$item['id'];
                                            ?>"
                                            <?php
                                            echo (
                                                (int)$selectedInventoryId
                                                ===
                                                (int)$item['id']
                                            )
                                                ? 'selected'
                                                : '';
                                            ?>
                                        >

                                            <?php

                                            $itemLabel =
                                                $item['item_name'];

                                            if (
                                                !empty(
                                                    $item['asset_number']
                                                )
                                            ) {
                                                $itemLabel .=
                                                    ' — Asset: ' .
                                                    $item['asset_number'];
                                            }

                                            $itemLabel .=
                                                ' — Stock: ' .
                                                $item['quantity'];

                                            echo e(
                                                $itemLabel
                                            );

                                            ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                                <div
                                    id="stockIndicator"
                                    class="stock-indicator"
                                ></div>

                            </div>


                            <!-- ITEM PREVIEW -->

                            <div
                                id="itemPreview"
                                class="preview-box"
                            >

                                <div class="preview-title">

                                    <i class="fas fa-box"></i>

                                    Equipment Information

                                </div>

                                <div class="preview-row">

                                    <span>
                                        Item
                                    </span>

                                    <span id="previewItemName">
                                        —
                                    </span>

                                </div>

                                <div class="preview-row">

                                    <span>
                                        Asset Number
                                    </span>

                                    <span id="previewAssetNumber">
                                        —
                                    </span>

                                </div>

                                <div class="preview-row">

                                    <span>
                                        Category
                                    </span>

                                    <span id="previewCategory">
                                        —
                                    </span>

                                </div>

                                <div class="preview-row">

                                    <span>
                                        Brand / Model
                                    </span>

                                    <span id="previewBrandModel">
                                        —
                                    </span>

                                </div>

                                <div class="preview-row">

                                    <span>
                                        Condition
                                    </span>

                                    <span id="previewCondition">
                                        —
                                    </span>

                                </div>

                                <div class="preview-row">

                                    <span>
                                        Available
                                    </span>

                                    <span id="previewAvailable">
                                        —
                                    </span>

                                </div>

                            </div>


                            <!-- QUANTITY + RETURN DATE -->

                            <div class="form-row">

                                <div class="form-group">

                                    <label for="quantity">

                                        <i class="fas fa-sort-numeric-up"></i>

                                        Quantity
                                        <span class="required">*</span>

                                    </label>

                                    <input
                                        type="number"
                                        id="quantity"
                                        name="quantity"
                                        value="<?php
                                            echo (int)$issueQuantity;
                                        ?>"
                                        min="1"
                                        step="1"
                                        required
                                    >

                                    <div class="form-note">
                                        Quantity must not exceed
                                        available stock.
                                    </div>

                                </div>


                                <div class="form-group">

                                    <label for="expected_return_date">

                                        <i class="fas fa-calendar-alt"></i>

                                        Expected Return

                                    </label>

                                    <input
                                        type="date"
                                        id="expected_return_date"
                                        name="expected_return_date"
                                        value="<?php
                                            echo e(
                                                $expectedReturnDate
                                            );
                                        ?>"
                                    >

                                    <div class="form-note">
                                        Optional.
                                    </div>

                                </div>

                            </div>


                            <!-- REMARKS -->

                            <div class="form-group">

                                <label for="remarks">

                                    <i class="fas fa-comment-alt"></i>

                                    Remarks

                                </label>

                                <textarea
                                    id="remarks"
                                    name="remarks"
                                    maxlength="2000"
                                    placeholder="Optional notes about this issue..."
                                ><?php
                                    echo e($remarks);
                                ?></textarea>

                            </div>


                            <!-- SUBMIT -->

                            <button
                                type="submit"
                                class="btn-issue"
                                id="submitButton"
                            >

                                <i class="fas fa-paper-plane"></i>

                                Issue Equipment

                            </button>


                            <div class="security-note">

                                <i class="fas fa-shield-alt"></i>

                                This transaction is protected by
                                security validation and recorded
                                in the system activity log.

                            </div>

                        </form>

                    <?php endif; ?>

                </div>


                <!-- =============================================
                     QUICK INFORMATION
                     ============================================= -->

                <div>

                    <div class="card">

                        <div class="card-title">

                            <i class="fas fa-info-circle"></i>

                            Issuing Guidelines

                        </div>

                        <span class="card-subtitle">

                            Important information before issuing
                            equipment.

                        </span>


                        <div class="alert alert-info">

                            <i class="fas fa-check-circle"></i>

                            <div>
                                Only active players can receive
                                equipment.
                            </div>

                        </div>


                        <div class="alert alert-info">

                            <i class="fas fa-boxes"></i>

                            <div>
                                The system automatically deducts
                                the issued quantity from inventory.
                            </div>

                        </div>


                        <div class="alert alert-info">

                            <i class="fas fa-clipboard-check"></i>

                            <div>
                                Player clearance is recalculated
                                automatically after an issue.
                            </div>

                        </div>


                        <div class="alert alert-info">

                            <i class="fas fa-history"></i>

                            <div>
                                Every issue is recorded in the
                                equipment issue history and
                                activity log.
                            </div>

                        </div>

                    </div>


                    <!-- INVENTORY SUMMARY -->

                    <div class="card">

                        <div class="card-title">

                            <i class="fas fa-warehouse"></i>

                            Available Inventory

                        </div>

                        <span class="card-subtitle">

                            Equipment currently available for
                            issuing.

                        </span>


                        <div class="table-wrapper">

                            <table class="data-table">

                                <thead>

                                    <tr>

                                        <th>
                                            Equipment
                                        </th>

                                        <th>
                                            Asset
                                        </th>

                                        <th>
                                            Stock
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <?php

                                    $summaryItems =
                                        array_slice(
                                            $items,
                                            0,
                                            8
                                        );

                                    ?>

                                    <?php if (
                                        empty($summaryItems)
                                    ): ?>

                                        <tr>

                                            <td
                                                colspan="3"
                                                style="text-align:center;"
                                            >
                                                No inventory available.
                                            </td>

                                        </tr>

                                    <?php else: ?>

                                        <?php foreach (
                                            $summaryItems
                                            as $item
                                        ): ?>

                                            <tr>

                                                <td>

                                                    <strong>
                                                        <?php
                                                        echo e(
                                                            $item[
                                                                'item_name'
                                                            ]
                                                        );
                                                        ?>
                                                    </strong>

                                                    <?php if (
                                                        !empty(
                                                            $item[
                                                                'category_name'
                                                            ]
                                                        )
                                                    ): ?>

                                                        <span
                                                            class="secondary-text"
                                                        >
                                                            <?php
                                                            echo e(
                                                                $item[
                                                                    'category_name'
                                                                ]
                                                            );
                                                            ?>
                                                        </span>

                                                    <?php endif; ?>

                                                </td>

                                                <td>

                                                    <?php
                                                    echo e(
                                                        $item[
                                                            'asset_number'
                                                        ]
                                                    );
                                                    ?>

                                                </td>

                                                <td>

                                                    <strong>
                                                        <?php
                                                        echo (int)
                                                            $item[
                                                                'quantity'
                                                            ];
                                                        ?>
                                                    </strong>

                                                    <?php
                                                    echo e(
                                                        $item['unit']
                                                            ?? 'Unit(s)'
                                                    );
                                                    ?>

                                                </td>

                                            </tr>

                                        <?php endforeach; ?>

                                    <?php endif; ?>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 RECENT ISSUES
                 ================================================= -->

            <div class="card">

                <div class="card-title">

                    <i class="fas fa-history"></i>

                    Recent Equipment Issues

                </div>

                <span class="card-subtitle">

                    The latest equipment issued to players.

                </span>


                <?php if (
                    !empty($recentIssues)
                ): ?>

                    <div class="table-wrapper">

                        <table class="data-table">

                            <thead>

                                <tr>

                                    <th>
                                        Player
                                    </th>

                                    <th>
                                        Equipment
                                    </th>

                                    <th>
                                        Quantity
                                    </th>

                                    <th>
                                        Issue Date
                                    </th>

                                    <th>
                                        Expected Return
                                    </th>

                                    <th>
                                        Issued By
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php foreach (
                                    $recentIssues
                                    as $issue
                                ): ?>

                                    <tr>

                                        <td>

                                            <span class="player-name">

                                                <?php
                                                echo e(
                                                    $issue[
                                                        'player_name'
                                                    ]
                                                );
                                                ?>

                                            </span>

                                            <span
                                                class="secondary-text"
                                            >

                                                <?php
                                                echo e(
                                                    $issue[
                                                        'student_id'
                                                    ]
                                                );
                                                ?>

                                                <?php if (
                                                    !empty(
                                                        $issue['team']
                                                    )
                                                ): ?>

                                                    —
                                                    <?php
                                                    echo e(
                                                        $issue['team']
                                                    );
                                                    ?>

                                                <?php endif; ?>

                                            </span>

                                        </td>


                                        <td>

                                            <strong>

                                                <?php
                                                echo e(
                                                    $issue[
                                                        'item_name'
                                                    ]
                                                );
                                                ?>

                                            </strong>

                                            <span
                                                class="secondary-text"
                                            >

                                                Asset:
                                                <?php
                                                echo e(
                                                    $issue[
                                                        'asset_number'
                                                    ]
                                                );
                                                ?>

                                            </span>

                                        </td>


                                        <td>

                                            <?php
                                            echo (int)
                                                $issue[
                                                    'quantity'
                                                ];
                                            ?>

                                            <?php
                                            echo e(
                                                $issue['unit']
                                                    ?? 'Unit(s)'
                                            );
                                            ?>

                                        </td>


                                        <td>

                                            <?php
                                            echo e(
                                                formatDateTime(
                                                    $issue[
                                                        'issue_date'
                                                    ]
                                                )
                                            );
                                            ?>

                                        </td>


                                        <td>

                                            <?php
                                            echo e(
                                                formatDate(
                                                    $issue[
                                                        'expected_return_date'
                                                    ]
                                                )
                                            );
                                            ?>

                                        </td>


                                        <td>

                                            <?php
                                            echo e(
                                                $issue[
                                                    'issued_by_name'
                                                ]
                                                ??
                                                'System'
                                            );
                                            ?>

                                        </td>


                                        <td>

                                            <?php

                                            $status =
                                                $issue[
                                                    'status'
                                                ];

                                            $badgeClass =
                                                'badge-issued';

                                            $badgeIcon =
                                                'fa-clock';

                                            if (
                                                $status
                                                ===
                                                'Overdue'
                                            ) {

                                                $badgeClass =
                                                    'badge-overdue';

                                                $badgeIcon =
                                                    'fa-exclamation-triangle';

                                            }

                                            elseif (
                                                $status
                                                ===
                                                'Returned'
                                            ) {

                                                $badgeClass =
                                                    'badge-returned';

                                                $badgeIcon =
                                                    'fa-check';

                                            }

                                            elseif (
                                                $status
                                                ===
                                                'Damaged'
                                            ) {

                                                $badgeClass =
                                                    'badge-damaged';

                                                $badgeIcon =
                                                    'fa-tools';

                                            }

                                            elseif (
                                                $status
                                                ===
                                                'Lost'
                                            ) {

                                                $badgeClass =
                                                    'badge-lost';

                                                $badgeIcon =
                                                    'fa-question-circle';
                                            }

                                            ?>

                                            <span
                                                class="badge <?php
                                                    echo e(
                                                        $badgeClass
                                                    );
                                                ?>"
                                            >

                                                <i
                                                    class="fas <?php
                                                        echo e(
                                                            $badgeIcon
                                                        );
                                                    ?>"
                                                ></i>

                                                <?php
                                                echo e(
                                                    $status
                                                );
                                                ?>

                                            </span>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <div class="empty-state">

                        <i class="fas fa-inbox"></i>

                        <p>
                            No equipment has been issued yet.
                        </p>

                        <small>
                            Completed equipment issues will
                            appear here.
                        </small>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </main>

</div>


<!-- ============================================================
     JAVASCRIPT
     ============================================================ -->

<script>

/* ============================================================
   SERVER DATA
   ============================================================ */

const playerData =
    <?php
    echo json_encode(
        $playerData,
        JSON_HEX_TAG
        | JSON_HEX_APOS
        | JSON_HEX_AMP
        | JSON_HEX_QUOT
    );
    ?>;


const itemData =
    <?php
    echo json_encode(
        $itemData,
        JSON_HEX_TAG
        | JSON_HEX_APOS
        | JSON_HEX_AMP
        | JSON_HEX_QUOT
    );
    ?>;


/* ============================================================
   ELEMENTS
   ============================================================ */

const playerSelect =
    document.getElementById('player_id');

const inventorySelect =
    document.getElementById('inventory_id');

const quantityInput =
    document.getElementById('quantity');

const playerPreview =
    document.getElementById('playerPreview');

const itemPreview =
    document.getElementById('itemPreview');

const stockIndicator =
    document.getElementById('stockIndicator');

const submitButton =
    document.getElementById('submitButton');

const issueForm =
    document.getElementById('issueForm');


/* ============================================================
   PLAYER PREVIEW
   ============================================================ */

function updatePlayerPreview() {

    if (!playerPreview) {
        return;
    }

    const playerId =
        playerSelect
        ? playerSelect.value
        : '';

    const player =
        playerData[playerId];

    if (!player) {

        playerPreview.classList.remove(
            'show'
        );

        return;
    }

    document.getElementById(
        'previewStudentId'
    ).textContent =
        player.student_id || '—';

    document.getElementById(
        'previewPlayerName'
    ).textContent =
        player.full_name || '—';

    document.getElementById(
        'previewTeam'
    ).textContent =
        player.team || '—';

    document.getElementById(
        'previewFaculty'
    ).textContent =
        player.faculty || '—';

    document.getElementById(
        'previewCourse'
    ).textContent =
        player.course || '—';

    playerPreview.classList.add(
        'show'
    );
}


/* ============================================================
   ITEM PREVIEW
   ============================================================ */

function updateItemPreview() {

    if (!inventorySelect) {
        return;
    }

    const itemId =
        inventorySelect.value;

    const item =
        itemData[itemId];

    if (!item) {

        if (itemPreview) {
            itemPreview.classList.remove(
                'show'
            );
        }

        if (stockIndicator) {
            stockIndicator.textContent =
                '';
        }

        return;
    }


    document.getElementById(
        'previewItemName'
    ).textContent =
        item.item_name || '—';


    document.getElementById(
        'previewAssetNumber'
    ).textContent =
        item.asset_number || '—';


    document.getElementById(
        'previewCategory'
    ).textContent =
        item.category || '—';


    let brandModel = '';

    if (item.brand) {
        brandModel += item.brand;
    }

    if (item.model) {

        if (brandModel) {
            brandModel += ' / ';
        }

        brandModel += item.model;
    }

    document.getElementById(
        'previewBrandModel'
    ).textContent =
        brandModel || '—';


    document.getElementById(
        'previewCondition'
    ).textContent =
        item.condition || '—';


    document.getElementById(
        'previewAvailable'
    ).textContent =
        item.quantity +
        ' ' +
        (item.unit || 'Unit(s)');


    if (itemPreview) {
        itemPreview.classList.add(
            'show'
        );
    }


    if (stockIndicator) {

        stockIndicator.innerHTML =
            '<i class="fas fa-boxes"></i> ' +
            '<strong>' +
            item.quantity +
            '</strong> ' +
            (item.unit || 'unit(s)') +
            ' available.';

        stockIndicator.classList.remove(
            'warning'
        );
    }


    validateQuantity();
}


/* ============================================================
   QUANTITY VALIDATION
   ============================================================ */

function validateQuantity() {

    if (!inventorySelect || !quantityInput) {
        return true;
    }

    const itemId =
        inventorySelect.value;

    const item =
        itemData[itemId];

    if (!item) {
        return true;
    }

    let quantity =
        parseInt(
            quantityInput.value,
            10
        );

    if (
        Number.isNaN(quantity)
        || quantity < 1
    ) {

        quantity = 1;

        quantityInput.value =
            quantity;
    }


    if (quantity > item.quantity) {

        quantityInput.setCustomValidity(
            'The requested quantity exceeds available stock.'
        );

        if (stockIndicator) {

            stockIndicator.innerHTML =
                '<i class="fas fa-exclamation-triangle"></i> ' +
                '<strong>Only ' +
                item.quantity +
                ' ' +
                (item.unit || 'unit(s)') +
                ' available.</strong>';

            stockIndicator.classList.add(
                'warning'
            );
        }

        return false;

    }


    quantityInput.setCustomValidity('');

    if (stockIndicator) {

        stockIndicator.innerHTML =
            '<i class="fas fa-boxes"></i> ' +
            '<strong>' +
            item.quantity +
            '</strong> ' +
            (item.unit || 'unit(s)') +
            ' available.';

        stockIndicator.classList.remove(
            'warning'
        );
    }

    return true;
}


/* ============================================================
   EVENT HANDLERS
   ============================================================ */

if (playerSelect) {

    playerSelect.addEventListener(
        'change',
        updatePlayerPreview
    );
}


if (inventorySelect) {

    inventorySelect.addEventListener(
        'change',
        updateItemPreview
    );
}


if (quantityInput) {

    quantityInput.addEventListener(
        'input',
        validateQuantity
    );

    quantityInput.addEventListener(
        'change',
        validateQuantity
    );
}


/* ============================================================
   FORM SUBMISSION PROTECTION
   ============================================================ */

if (issueForm) {

    issueForm.addEventListener(
        'submit',
        function(event) {

            if (!validateQuantity()) {

                event.preventDefault();

                alert(
                    'The requested quantity exceeds available stock.'
                );

                return;
            }


            /*
             * Prevent accidental double submission.
             */
            if (
                submitButton
                &&
                submitButton.disabled
            ) {

                event.preventDefault();

                return;
            }


            if (submitButton) {

                submitButton.disabled =
                    true;

                submitButton.innerHTML =
                    '<i class="fas fa-spinner fa-spin"></i> ' +
                    'Processing...';
            }

        }
    );
}


/* ============================================================
   INITIALIZE
   ============================================================ */

document.addEventListener(
    'DOMContentLoaded',
    function() {

        updatePlayerPreview();

        updateItemPreview();

        validateQuantity();

    }
);

</script>

</body>

</html>