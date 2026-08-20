<?php
/**
 * ============================================================
 * MACHakos University Sports Management System V2
 * File: includes/functions.php
 * Purpose: Shared application helper functions
 * Database: machakos_sports_v2
 * ============================================================
 *
 * This file contains shared helper functions used throughout
 * the Machakos University Sports Management System V2.
 *
 * Database connection:
 *   includes/config.php
 *
 * Expected PDO variable:
 *   $pdo
 *
 * Compatible with:
 *   users
 *   roles
 *   players
 *   inventory_items
 *   equipment_categories
 *   suppliers
 *   equipment_issues
 *   equipment_returns
 *   equipment_incidents
 *   player_clearance
 *   tournaments
 *   tournament_fixtures
 *   tournament_registrations
 *   match_results
 *   activity_log
 *
 * Database:
 *   machakos_sports_v2
 */

/* ============================================================
   BASIC HELPERS
   ============================================================ */

/**
 * Escape output safely for HTML.
 */
if (!function_exists('e')) {
    function e($value)
    {
        return htmlspecialchars(
            (string)$value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


/**
 * Safely convert a value to integer.
 */
if (!function_exists('safeInt')) {
    function safeInt($value, $default = 0)
    {
        return is_numeric($value)
            ? (int)$value
            : $default;
    }
}


/**
 * Safely convert a value to float.
 */
if (!function_exists('safeFloat')) {
    function safeFloat($value, $default = 0.00)
    {
        return is_numeric($value)
            ? (float)$value
            : $default;
    }
}


/**
 * Format money using Kenyan Shillings.
 */
if (!function_exists('formatMoney')) {
    function formatMoney($amount)
    {
        return 'KES ' . number_format(
            safeFloat($amount),
            2
        );
    }
}


/**
 * Format date safely.
 */
if (!function_exists('formatDate')) {
    function formatDate($date, $format = 'd M Y')
    {
        if (empty($date)) {
            return 'N/A';
        }

        $timestamp = strtotime($date);

        if ($timestamp === false) {
            return 'N/A';
        }

        return date($format, $timestamp);
    }
}


/**
 * Format datetime safely.
 */
if (!function_exists('formatDateTime')) {
    function formatDateTime($datetime, $format = 'd M Y H:i')
    {
        if (empty($datetime)) {
            return 'N/A';
        }

        $timestamp = strtotime($datetime);

        if ($timestamp === false) {
            return 'N/A';
        }

        return date($format, $timestamp);
    }
}


/**
 * Get current logged-in user ID.
 */
if (!function_exists('getCurrentUserId')) {
    function getCurrentUserId()
    {
        return isset($_SESSION['user_id'])
            ? (int)$_SESSION['user_id']
            : null;
    }
}


/**
 * Get current logged-in user's name.
 */
if (!function_exists('getCurrentUserName')) {
    function getCurrentUserName()
    {
        return $_SESSION['full_name']
            ?? $_SESSION['username']
            ?? 'System User';
    }
}


/**
 * Get current logged-in username.
 */
if (!function_exists('getCurrentUsername')) {
    function getCurrentUsername()
    {
        return $_SESSION['username']
            ?? 'system';
    }
}


/* ============================================================
   ACTIVITY LOG
   ============================================================ */

/**
 * Record system activity.
 *
 * activity_log columns:
 *   user_id
 *   action
 *   description
 *   ip_address
 *   browser
 */
if (!function_exists('logActivity')) {
    function logActivity(
        PDO $pdo,
        $action,
        $description = null,
        $userId = null
    ) {
        try {

            if ($userId === null) {
                $userId = getCurrentUserId();
            }

            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

            $browser = $_SERVER['HTTP_USER_AGENT'] ?? null;

            $sql = "
                INSERT INTO activity_log
                (
                    user_id,
                    action,
                    description,
                    ip_address,
                    browser
                )
                VALUES
                (
                    :user_id,
                    :action,
                    :description,
                    :ip_address,
                    :browser
                )
            ";

            $stmt = $pdo->prepare($sql);

            return $stmt->execute([
                ':user_id'     => $userId,
                ':action'      => $action,
                ':description' => $description,
                ':ip_address'  => $ipAddress,
                ':browser'     => $browser
            ]);

        } catch (PDOException $e) {

            /*
             * Activity logging must never prevent the main
             * operation from completing.
             */
            error_log(
                'Activity log error: ' . $e->getMessage()
            );

            return false;
        }
    }
}


/**
 * Get recent activity for dashboard.
 */
if (!function_exists('getDashboardRecentActivity')) {
    function getDashboardRecentActivity(PDO $pdo, $limit = 10)
    {
        $limit = max(
            1,
            min(100, (int)$limit)
        );

        $sql = "
            SELECT
                al.id,
                al.user_id,
                al.action,
                al.description,
                al.ip_address,
                al.browser,
                al.created_at,
                u.username,
                u.full_name
            FROM activity_log al
            LEFT JOIN users u
                ON u.id = al.user_id
            ORDER BY
                al.created_at DESC,
                al.id DESC
            LIMIT {$limit}
        ";

        return $pdo
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }
}


/* ============================================================
   DASHBOARD STATISTICS
   ============================================================ */

/**
 * Get total players.
 *
 * Excludes graduated players.
 */
if (!function_exists('getTotalPlayers')) {
    function getTotalPlayers(PDO $pdo)
    {
        $stmt = $pdo->query("
            SELECT COUNT(*)
            FROM players
            WHERE status <> 'Graduated'
        ");

        return (int)$stmt->fetchColumn();
    }
}


/**
 * Get active players.
 */
if (!function_exists('getActivePlayers')) {
    function getActivePlayers(PDO $pdo)
    {
        $stmt = $pdo->query("
            SELECT COUNT(*)
            FROM players
            WHERE status = 'Active'
        ");

        return (int)$stmt->fetchColumn();
    }
}


/**
 * Get total inventory records.
 */
if (!function_exists('getTotalInventoryItems')) {
    function getTotalInventoryItems(PDO $pdo)
    {
        $stmt = $pdo->query("
            SELECT COUNT(*)
            FROM inventory_items
            WHERE status <> 'Disposed'
        ");

        return (int)$stmt->fetchColumn();
    }
}


/**
 * Get total stock quantity.
 *
 * This represents the quantity physically recorded in the
 * inventory_items table.
 */
if (!function_exists('getTotalStock')) {
    function getTotalStock(PDO $pdo)
    {
        $stmt = $pdo->query("
            SELECT
                COALESCE(
                    SUM(
                        CASE
                            WHEN status <> 'Disposed'
                            THEN quantity
                            ELSE 0
                        END
                    ),
                    0
                )
            FROM inventory_items
        ");

        return (int)$stmt->fetchColumn();
    }
}


/**
 * Get currently issued quantity.
 */
if (!function_exists('getIssuedItemsCount')) {
    function getIssuedItemsCount(PDO $pdo)
    {
        $stmt = $pdo->query("
            SELECT
                COALESCE(
                    SUM(quantity),
                    0
                )
            FROM equipment_issues
            WHERE status IN ('Issued', 'Overdue')
        ");

        return (int)$stmt->fetchColumn();
    }
}


/**
 * Get pending clearance count.
 */
if (!function_exists('getPendingClearanceCount')) {
    function getPendingClearanceCount(PDO $pdo)
    {
        $stmt = $pdo->query("
            SELECT COUNT(*)
            FROM player_clearance
            WHERE clearance_status = 'Pending'
        ");

        return (int)$stmt->fetchColumn();
    }
}


/**
 * Get cleared players count.
 */
if (!function_exists('getClearedPlayersCount')) {
    function getClearedPlayersCount(PDO $pdo)
    {
        $stmt = $pdo->query("
            SELECT COUNT(*)
            FROM player_clearance
            WHERE clearance_status = 'Cleared'
        ");

        return (int)$stmt->fetchColumn();
    }
}


/**
 * Get total teams.
 *
 * V2 does not contain a dedicated teams table.
 * Teams are represented by players.team.
 */
if (!function_exists('getTotalTeams')) {
    function getTotalTeams(PDO $pdo)
    {
        $stmt = $pdo->query("
            SELECT COUNT(DISTINCT TRIM(team))
            FROM players
            WHERE team IS NOT NULL
              AND TRIM(team) <> ''
        ");

        return (int)$stmt->fetchColumn();
    }
}


/**
 * Get low-stock inventory.
 */
if (!function_exists('getLowStockItems')) {
    function getLowStockItems(PDO $pdo)
    {
        $sql = "
            SELECT
                id,
                category_id,
                supplier_id,
                asset_number,
                barcode,
                qr_code,
                serial_number,
                item_name,
                description,
                brand,
                model,
                quantity,
                minimum_stock,
                unit,
                purchase_price,
                replacement_cost,
                purchase_date,
                item_condition,
                status,
                storage_location,
                remarks,
                created_by,
                updated_by,
                created_at,
                updated_at
            FROM inventory_items
            WHERE quantity <= minimum_stock
              AND status NOT IN ('Disposed', 'Lost')
            ORDER BY
                quantity ASC,
                item_name ASC
        ";

        return $pdo
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }
}


/**
 * Get available inventory items.
 */
if (!function_exists('getAvailableItems')) {
    function getAvailableItems(PDO $pdo)
    {
        $sql = "
            SELECT
                id,
                asset_number,
                item_name,
                quantity,
                minimum_stock,
                unit,
                item_condition,
                status,
                storage_location
            FROM inventory_items
            WHERE status = 'Available'
              AND quantity > 0
            ORDER BY
                item_name ASC
        ";

        return $pdo
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }
}


/* ============================================================
   PLAYER HELPERS
   ============================================================ */

/**
 * Get quick list of active players.
 */
if (!function_exists('getQuickPlayers')) {
    function getQuickPlayers(PDO $pdo, $limit = 10)
    {
        $limit = max(
            1,
            min(100, (int)$limit)
        );

        $sql = "
            SELECT
                id,
                student_id,
                registration_no,
                first_name,
                middle_name,
                last_name,
                full_name,
                gender,
                team,
                faculty,
                department,
                course,
                year_of_study,
                profile_photo,
                status
            FROM players
            WHERE status = 'Active'
            ORDER BY
                full_name ASC
            LIMIT {$limit}
        ";

        return $pdo
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }
}


/**
 * Get player by ID.
 */
if (!function_exists('getPlayerById')) {
    function getPlayerById(PDO $pdo, $playerId)
    {
        $stmt = $pdo->prepare("
            SELECT *
            FROM players
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => (int)$playerId
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}


/**
 * Get player by student ID.
 */
if (!function_exists('getPlayerByStudentId')) {
    function getPlayerByStudentId(PDO $pdo, $studentId)
    {
        $stmt = $pdo->prepare("
            SELECT *
            FROM players
            WHERE student_id = :student_id
            LIMIT 1
        ");

        $stmt->execute([
            ':student_id' => trim($studentId)
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}


/**
 * Search players.
 */
if (!function_exists('searchPlayers')) {
    function searchPlayers(PDO $pdo, $search, $limit = 50)
    {
        $limit = max(
            1,
            min(200, (int)$limit)
        );

        $search = trim($search);

        if ($search === '') {
            return [];
        }

        $stmt = $pdo->prepare("
            SELECT
                id,
                student_id,
                registration_no,
                full_name,
                gender,
                faculty,
                department,
                course,
                year_of_study,
                team,
                playing_position,
                status
            FROM players
            WHERE
                student_id LIKE :search
                OR registration_no LIKE :search
                OR full_name LIKE :search
                OR team LIKE :search
                OR course LIKE :search
            ORDER BY full_name ASC
            LIMIT {$limit}
        ");

        $stmt->execute([
            ':search' => '%' . $search . '%'
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}


/* ============================================================
   EQUIPMENT / INVENTORY HELPERS
   ============================================================ */

/**
 * Get inventory item by ID.
 */
if (!function_exists('getInventoryItemById')) {
    function getInventoryItemById(PDO $pdo, $inventoryId)
    {
        $stmt = $pdo->prepare("
            SELECT
                ii.*,
                c.category_name,
                s.supplier_name
            FROM inventory_items ii
            LEFT JOIN equipment_categories c
                ON c.id = ii.category_id
            LEFT JOIN suppliers s
                ON s.id = ii.supplier_id
            WHERE ii.id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => (int)$inventoryId
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}


/**
 * Get inventory item by asset number.
 */
if (!function_exists('getInventoryItemByAssetNumber')) {
    function getInventoryItemByAssetNumber(PDO $pdo, $assetNumber)
    {
        $stmt = $pdo->prepare("
            SELECT
                ii.*,
                c.category_name,
                s.supplier_name
            FROM inventory_items ii
            LEFT JOIN equipment_categories c
                ON c.id = ii.category_id
            LEFT JOIN suppliers s
                ON s.id = ii.supplier_id
            WHERE ii.asset_number = :asset_number
            LIMIT 1
        ");

        $stmt->execute([
            ':asset_number' => trim($assetNumber)
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}


/**
 * Get equipment issues for a player.
 */
if (!function_exists('getPlayerEquipmentIssues')) {
    function getPlayerEquipmentIssues(PDO $pdo, $playerId)
    {
        $stmt = $pdo->prepare("
            SELECT
                ei.*,

                ii.asset_number,
                ii.item_name,
                ii.brand,
                ii.model,
                ii.unit,
                ii.item_condition,

                p.full_name,
                p.student_id,
                p.team

            FROM equipment_issues ei

            INNER JOIN inventory_items ii
                ON ii.id = ei.inventory_id

            INNER JOIN players p
                ON p.id = ei.player_id

            WHERE ei.player_id = :player_id

            ORDER BY
                ei.issue_date DESC,
                ei.id DESC
        ");

        $stmt->execute([
            ':player_id' => (int)$playerId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}


/**
 * Get outstanding equipment.
 */
if (!function_exists('getOutstandingEquipment')) {
    function getOutstandingEquipment(PDO $pdo)
    {
        $sql = "
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
                p.registration_no,
                p.full_name,
                p.team,

                ii.asset_number,
                ii.item_name,
                ii.brand,
                ii.model,
                ii.unit

            FROM equipment_issues ei

            INNER JOIN players p
                ON p.id = ei.player_id

            INNER JOIN inventory_items ii
                ON ii.id = ei.inventory_id

            WHERE ei.status IN ('Issued', 'Overdue')

            ORDER BY
                CASE
                    WHEN ei.expected_return_date IS NULL
                    THEN 1
                    ELSE 0
                END,
                ei.expected_return_date ASC,
                ei.issue_date DESC
        ";

        return $pdo
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }
}


/**
 * Get a specific equipment issue.
 */
if (!function_exists('getEquipmentIssueById')) {
    function getEquipmentIssueById(PDO $pdo, $issueId)
    {
        $stmt = $pdo->prepare("
            SELECT
                ei.*,

                p.student_id,
                p.registration_no,
                p.full_name,
                p.team,

                ii.asset_number,
                ii.item_name,
                ii.brand,
                ii.model,
                ii.unit,
                ii.quantity AS current_inventory_quantity

            FROM equipment_issues ei

            INNER JOIN players p
                ON p.id = ei.player_id

            INNER JOIN inventory_items ii
                ON ii.id = ei.inventory_id

            WHERE ei.id = :id

            LIMIT 1
        ");

        $stmt->execute([
            ':id' => (int)$issueId
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}


/* ============================================================
   CLEARANCE HELPERS
   ============================================================ */

/**
 * Get player's clearance record.
 */
if (!function_exists('getPlayerClearance')) {
    function getPlayerClearance(PDO $pdo, $playerId)
    {
        $stmt = $pdo->prepare("
            SELECT
                pc.*,

                p.student_id,
                p.registration_no,
                p.full_name,
                p.team,
                p.faculty,
                p.course,
                p.year_of_study

            FROM player_clearance pc

            INNER JOIN players p
                ON p.id = pc.player_id

            WHERE pc.player_id = :player_id

            LIMIT 1
        ");

        $stmt->execute([
            ':player_id' => (int)$playerId
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}


/**
 * Calculate player's clearance statistics.
 *
 * Uses equipment_issues for issue status and
 * equipment_returns for fines.
 */
if (!function_exists('calculatePlayerClearance')) {
    function calculatePlayerClearance(PDO $pdo, $playerId)
    {
        $playerId = (int)$playerId;

        $sql = "
            SELECT

                COALESCE(
                    SUM(
                        CASE
                            WHEN ei.status IN ('Issued', 'Overdue')
                            THEN ei.quantity
                            ELSE 0
                        END
                    ),
                    0
                ) AS outstanding_items,

                COALESCE(
                    SUM(
                        CASE
                            WHEN ei.status = 'Returned'
                            THEN ei.quantity
                            ELSE 0
                        END
                    ),
                    0
                ) AS returned_items,

                COALESCE(
                    SUM(
                        CASE
                            WHEN ei.status = 'Lost'
                            THEN ei.quantity
                            ELSE 0
                        END
                    ),
                    0
                ) AS lost_items,

                COALESCE(
                    SUM(
                        CASE
                            WHEN ei.status = 'Damaged'
                            THEN ei.quantity
                            ELSE 0
                        END
                    ),
                    0
                ) AS damaged_items

            FROM equipment_issues ei

            WHERE ei.player_id = :player_id
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':player_id' => $playerId
        ]);

        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$stats) {
            $stats = [
                'outstanding_items' => 0,
                'returned_items'    => 0,
                'lost_items'        => 0,
                'damaged_items'     => 0
            ];
        }

        /**
         * Calculate total fines.
         */
        $fineStmt = $pdo->prepare("
            SELECT
                COALESCE(
                    SUM(er.fine_amount),
                    0
                )
            FROM equipment_returns er

            INNER JOIN equipment_issues ei
                ON ei.id = er.issue_id

            WHERE ei.player_id = :player_id
        ");

        $fineStmt->execute([
            ':player_id' => $playerId
        ]);

        $stats['total_fine'] =
            (float)$fineStmt->fetchColumn();

        return $stats;
    }
}


/**
 * Update or create player clearance.
 */
if (!function_exists('updatePlayerClearance')) {
    function updatePlayerClearance(PDO $pdo, $playerId)
    {
        $playerId = (int)$playerId;

        if ($playerId <= 0) {
            return false;
        }

        $player = getPlayerById(
            $pdo,
            $playerId
        );

        if (!$player) {
            return false;
        }

        $stats = calculatePlayerClearance(
            $pdo,
            $playerId
        );

        $outstanding = (int)$stats['outstanding_items'];

        /**
         * A player is considered cleared only when there
         * are no outstanding items.
         *
         * Fines do not automatically prevent clearance here;
         * the administrator can use the fine amount in the
         * clearance workflow.
         */
        $clearanceStatus =
            $outstanding === 0
            ? 'Cleared'
            : 'Pending';

        $existing = getPlayerClearance(
            $pdo,
            $playerId
        );

        if ($existing) {

            $stmt = $pdo->prepare("
                UPDATE player_clearance
                SET
                    outstanding_items = :outstanding_items,
                    returned_items = :returned_items,
                    lost_items = :lost_items,
                    damaged_items = :damaged_items,
                    total_fine = :total_fine,
                    clearance_status = :clearance_status,
                    updated_at = CURRENT_TIMESTAMP
                WHERE player_id = :player_id
            ");

        } else {

            $stmt = $pdo->prepare("
                INSERT INTO player_clearance
                (
                    player_id,
                    outstanding_items,
                    returned_items,
                    lost_items,
                    damaged_items,
                    total_fine,
                    clearance_status
                )
                VALUES
                (
                    :player_id,
                    :outstanding_items,
                    :returned_items,
                    :lost_items,
                    :damaged_items,
                    :total_fine,
                    :clearance_status
                )
            ");
        }

        return $stmt->execute([
            ':player_id'         => $playerId,
            ':outstanding_items' => $outstanding,
            ':returned_items'    => (int)$stats['returned_items'],
            ':lost_items'        => (int)$stats['lost_items'],
            ':damaged_items'     => (int)$stats['damaged_items'],
            ':total_fine'        => safeFloat($stats['total_fine']),
            ':clearance_status'  => $clearanceStatus
        ]);
    }
}


/**
 * Get clearance statistics for all players.
 */
if (!function_exists('getClearanceReport')) {
    function getClearanceReport(PDO $pdo)
    {
        $sql = "
            SELECT
                pc.id,
                pc.player_id,
                pc.outstanding_items,
                pc.returned_items,
                pc.lost_items,
                pc.damaged_items,
                pc.total_fine,
                pc.clearance_status,
                pc.approved_by,
                pc.approval_date,
                pc.remarks,
                pc.created_at,
                pc.updated_at,

                p.student_id,
                p.registration_no,
                p.full_name,
                p.team,
                p.faculty,
                p.department,
                p.course,
                p.year_of_study,
                p.status

            FROM player_clearance pc

            INNER JOIN players p
                ON p.id = pc.player_id

            ORDER BY
                CASE
                    WHEN pc.clearance_status = 'Pending'
                    THEN 0
                    ELSE 1
                END,
                p.full_name ASC
        ";

        return $pdo
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }
}


/* ============================================================
   REPORT FUNCTIONS
   ============================================================ */

/**
 * Generate players report.
 */
if (!function_exists('generatePlayersReport')) {
    function generatePlayersReport(PDO $pdo = null)
    {
        if ($pdo === null) {

            global $pdo;

            if (
                !isset($pdo) ||
                !($pdo instanceof PDO)
            ) {
                throw new RuntimeException(
                    'Database connection is not available.'
                );
            }
        }

        $sql = "
            SELECT

                p.id,
                p.student_id,
                p.registration_no,
                p.full_name,
                p.team,
                p.faculty,
                p.department,
                p.course,
                p.year_of_study,
                p.status,

                COALESCE(
                    SUM(ei.quantity),
                    0
                ) AS items_issued,

                COALESCE(
                    SUM(
                        CASE
                            WHEN ei.status = 'Returned'
                            THEN ei.quantity
                            ELSE 0
                        END
                    ),
                    0
                ) AS items_returned,

                COALESCE(
                    SUM(
                        CASE
                            WHEN ei.status IN ('Issued', 'Overdue')
                            THEN ei.quantity
                            ELSE 0
                        END
                    ),
                    0
                ) AS items_outstanding

            FROM players p

            LEFT JOIN equipment_issues ei
                ON ei.player_id = p.id

            GROUP BY
                p.id,
                p.student_id,
                p.registration_no,
                p.full_name,
                p.team,
                p.faculty,
                p.department,
                p.course,
                p.year_of_study,
                p.status

            ORDER BY
                p.full_name ASC
        ";

        return $pdo
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }
}


/**
 * Generate inventory/equipment report.
 *
 * Filters:
 *   all
 *   outstanding
 *   returned
 *
 * Optional dates:
 *   date_from
 *   date_to
 */
if (!function_exists('generateItemsReport')) {
    function generateItemsReport(
        $filter = 'all',
        $date_from = null,
        $date_to = null,
        PDO $pdo = null
    ) {

        if ($pdo === null) {

            global $pdo;

            if (
                !isset($pdo) ||
                !($pdo instanceof PDO)
            ) {
                throw new RuntimeException(
                    'Database connection is not available.'
                );
            }
        }

        $allowedFilters = [
            'all',
            'outstanding',
            'returned'
        ];

        if (
            !in_array(
                $filter,
                $allowedFilters,
                true
            )
        ) {
            $filter = 'all';
        }

        $where = [];
        $params = [];

        /**
         * Date range applies to issue date.
         */
        if (!empty($date_from)) {

            $where[] = "
                ei.issue_date >= :date_from
            ";

            $params[':date_from'] =
                $date_from . ' 00:00:00';
        }

        if (!empty($date_to)) {

            $where[] = "
                ei.issue_date <= :date_to
            ";

            $params[':date_to'] =
                $date_to . ' 23:59:59';
        }

        /**
         * Important:
         * Do not put the filter conditions into WHERE because
         * the report uses LEFT JOIN and aggregate calculations.
         */
        $whereSql = '';

        if (!empty($where)) {

            $whereSql =
                'WHERE ' .
                implode(
                    ' AND ',
                    $where
                );
        }

        $sql = "
            SELECT

                ii.id,
                ii.asset_number,
                ii.item_name,
                ii.brand,
                ii.model,
                ii.unit,
                ii.quantity AS current_stock,
                ii.minimum_stock,
                ii.item_condition,
                ii.status,
                ii.storage_location,

                COALESCE(
                    SUM(ei.quantity),
                    0
                ) AS total_issued,

                COALESCE(
                    SUM(
                        CASE
                            WHEN ei.status = 'Returned'
                            THEN ei.quantity
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_returned,

                COALESCE(
                    SUM(
                        CASE
                            WHEN ei.status IN ('Issued', 'Overdue')
                            THEN ei.quantity
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_outstanding

            FROM inventory_items ii

            LEFT JOIN equipment_issues ei
                ON ei.inventory_id = ii.id

            {$whereSql}

            GROUP BY
                ii.id,
                ii.asset_number,
                ii.item_name,
                ii.brand,
                ii.model,
                ii.unit,
                ii.quantity,
                ii.minimum_stock,
                ii.item_condition,
                ii.status,
                ii.storage_location
        ";

        $having = [];

        if ($filter === 'outstanding') {

            $having[] = "
                total_outstanding > 0
            ";
        }

        if ($filter === 'returned') {

            $having[] = "
                total_returned > 0
            ";
        }

        if (!empty($having)) {

            $sql .=
                ' HAVING ' .
                implode(
                    ' AND ',
                    $having
                );
        }

        $sql .= "
            ORDER BY
                ii.item_name ASC
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute($params);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }
}


/* ============================================================
   TOURNAMENT HELPERS
   ============================================================ */

/**
 * Get tournaments.
 */
if (!function_exists('getTournaments')) {
    function getTournaments(
        PDO $pdo,
        $status = null
    ) {

        if ($status !== null) {

            $stmt = $pdo->prepare("
                SELECT *
                FROM tournaments
                WHERE status = :status
                ORDER BY
                    start_date DESC,
                    tournament_name ASC
            ");

            $stmt->execute([
                ':status' => $status
            ]);

        } else {

            $stmt = $pdo->query("
                SELECT *
                FROM tournaments
                ORDER BY
                    start_date DESC,
                    tournament_name ASC
            ");
        }

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }
}


/**
 * Get tournament by ID.
 */
if (!function_exists('getTournamentById')) {
    function getTournamentById(
        PDO $pdo,
        $tournamentId
    ) {

        $stmt = $pdo->prepare("
            SELECT *
            FROM tournaments
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => (int)$tournamentId
        ]);

        return $stmt->fetch(
            PDO::FETCH_ASSOC
        );
    }
}


/**
 * Get tournament fixtures.
 */
if (!function_exists('getTournamentFixtures')) {
    function getTournamentFixtures(
        PDO $pdo,
        $tournamentId
    ) {

        $stmt = $pdo->prepare("
            SELECT

                tf.*,

                mr.home_score,
                mr.away_score,
                mr.winner,
                mr.remarks AS result_remarks,
                mr.recorded_at

            FROM tournament_fixtures tf

            LEFT JOIN match_results mr
                ON mr.fixture_id = tf.id

            WHERE tf.tournament_id = :tournament_id

            ORDER BY
                tf.fixture_date ASC,
                tf.id ASC
        ");

        $stmt->execute([
            ':tournament_id' =>
                (int)$tournamentId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }
}


/**
 * Get tournament registrations.
 */
if (!function_exists('getTournamentRegistrations')) {
    function getTournamentRegistrations(
        PDO $pdo,
        $tournamentId
    ) {

        $stmt = $pdo->prepare("
            SELECT

                tr.*,

                p.student_id,
                p.registration_no,
                p.full_name,
                p.team,
                p.faculty,
                p.department,
                p.course,
                p.year_of_study

            FROM tournament_registrations tr

            INNER JOIN players p
                ON p.id = tr.player_id

            WHERE tr.tournament_id = :tournament_id

            ORDER BY
                p.full_name ASC
        ");

        $stmt->execute([
            ':tournament_id' =>
                (int)$tournamentId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }
}


/* ============================================================
   MATCH RESULT HELPERS
   ============================================================ */

/**
 * Get result for a fixture.
 */
if (!function_exists('getMatchResult')) {
    function getMatchResult(
        PDO $pdo,
        $fixtureId
    ) {

        $stmt = $pdo->prepare("
            SELECT

                mr.*,

                tf.tournament_id,
                tf.home_team,
                tf.away_team,
                tf.fixture_date,
                tf.venue

            FROM match_results mr

            INNER JOIN tournament_fixtures tf
                ON tf.id = mr.fixture_id

            WHERE mr.fixture_id = :fixture_id

            LIMIT 1
        ");

        $stmt->execute([
            ':fixture_id' =>
                (int)$fixtureId
        ]);

        return $stmt->fetch(
            PDO::FETCH_ASSOC
        );
    }
}


/**
 * Get all match results for a tournament.
 */
if (!function_exists('getTournamentResults')) {
    function getTournamentResults(
        PDO $pdo,
        $tournamentId
    ) {

        $stmt = $pdo->prepare("
            SELECT

                mr.*,

                tf.tournament_id,
                tf.home_team,
                tf.away_team,
                tf.fixture_date,
                tf.venue

            FROM match_results mr

            INNER JOIN tournament_fixtures tf
                ON tf.id = mr.fixture_id

            WHERE tf.tournament_id = :tournament_id

            ORDER BY
                tf.fixture_date ASC,
                mr.id ASC
        ");

        $stmt->execute([
            ':tournament_id' =>
                (int)$tournamentId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }
}


/* ============================================================
   OVERDUE EQUIPMENT
   ============================================================ */

/**
 * Mark issued equipment as overdue.
 *
 * This changes only the equipment_issues status.
 *
 * Inventory quantity is NOT modified here.
 */
if (!function_exists('updateOverdueIssues')) {
    function updateOverdueIssues(PDO $pdo)
    {
        $stmt = $pdo->prepare("
            UPDATE equipment_issues

            SET status = 'Overdue'

            WHERE status = 'Issued'

              AND expected_return_date IS NOT NULL

              AND expected_return_date < CURDATE()
        ");

        return $stmt->execute();
    }
}


/**
 * Count overdue equipment issue records.
 */
if (!function_exists('getOverdueIssuesCount')) {
    function getOverdueIssuesCount(PDO $pdo)
    {
        $stmt = $pdo->query("
            SELECT COUNT(*)
            FROM equipment_issues
            WHERE status IN ('Issued', 'Overdue')
              AND expected_return_date IS NOT NULL
              AND expected_return_date < CURDATE()
        ");

        return (int)$stmt->fetchColumn();
    }
}


/**
 * Get overdue equipment.
 */
if (!function_exists('getOverdueIssues')) {
    function getOverdueIssues(PDO $pdo)
    {
        $stmt = $pdo->query("
            SELECT

                ei.*,

                p.student_id,
                p.full_name,
                p.team,

                ii.asset_number,
                ii.item_name,
                ii.unit

            FROM equipment_issues ei

            INNER JOIN players p
                ON p.id = ei.player_id

            INNER JOIN inventory_items ii
                ON ii.id = ei.inventory_id

            WHERE ei.status IN ('Issued', 'Overdue')

              AND ei.expected_return_date IS NOT NULL

              AND ei.expected_return_date < CURDATE()

            ORDER BY
                ei.expected_return_date ASC
        ");

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }
}


/* ============================================================
   INVENTORY AVAILABILITY
   ============================================================ */

/**
 * Get current available quantity for an inventory item.
 *
 * V2 treats inventory_items.quantity as the current
 * available physical stock.
 */
if (!function_exists('getInventoryAvailableQuantity')) {
    function getInventoryAvailableQuantity(
        PDO $pdo,
        $inventoryId
    ) {

        $stmt = $pdo->prepare("
            SELECT
                COALESCE(quantity, 0)
            FROM inventory_items
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => (int)$inventoryId
        ]);

        $result = $stmt->fetchColumn();

        return $result === false
            ? 0
            : (int)$result;
    }
}


/**
 * Check whether requested inventory quantity is available.
 */
if (!function_exists('isInventoryAvailable')) {
    function isInventoryAvailable(
        PDO $pdo,
        $inventoryId,
        $requestedQuantity
    ) {

        $requestedQuantity =
            (int)$requestedQuantity;

        if ($requestedQuantity <= 0) {
            return false;
        }

        $available =
            getInventoryAvailableQuantity(
                $pdo,
                $inventoryId
            );

        return $available >=
            $requestedQuantity;
    }
}


/* ============================================================
   DASHBOARD CHART DATA
   ============================================================ */

/**
 * Team distribution.
 */
if (!function_exists('getTeamDistribution')) {
    function getTeamDistribution(PDO $pdo)
    {
        $stmt = $pdo->query("
            SELECT
                TRIM(team) AS team,
                COUNT(*) AS player_count

            FROM players

            WHERE team IS NOT NULL
              AND TRIM(team) <> ''
              AND status = 'Active'

            GROUP BY
                TRIM(team)

            ORDER BY
                player_count DESC,
                team ASC
        ");

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }
}


/**
 * Monthly equipment issue data.
 *
 * Returns the last N months that contain issue records.
 */
if (!function_exists('getMonthlyIssueData')) {
    function getMonthlyIssueData(
        PDO $pdo,
        $months = 6
    ) {

        $months = max(
            1,
            min(24, (int)$months)
        );

        $stmt = $pdo->query("
            SELECT

                DATE_FORMAT(
                    issue_date,
                    '%Y-%m'
                ) AS month,

                DATE_FORMAT(
                    issue_date,
                    '%b %Y'
                ) AS month_label,

                COALESCE(
                    SUM(quantity),
                    0
                ) AS total_issued

            FROM equipment_issues

            WHERE issue_date >= DATE_SUB(
                CURDATE(),
                INTERVAL {$months} MONTH
            )

            GROUP BY
                DATE_FORMAT(
                    issue_date,
                    '%Y-%m'
                ),

                DATE_FORMAT(
                    issue_date,
                    '%b %Y'
                )

            ORDER BY
                month ASC
        ");

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }
}


/**
 * Inventory status distribution.
 */
if (!function_exists('getInventoryStatusData')) {
    function getInventoryStatusData(PDO $pdo)
    {
        $stmt = $pdo->query("
            SELECT
                status,
                COUNT(*) AS item_count

            FROM inventory_items

            GROUP BY
                status

            ORDER BY
                item_count DESC
        ");

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }
}


/**
 * Inventory category distribution.
 */
if (!function_exists('getInventoryCategoryData')) {
    function getInventoryCategoryData(PDO $pdo)
    {
        $stmt = $pdo->query("
            SELECT

                c.category_name,

                COUNT(ii.id) AS item_count,

                COALESCE(
                    SUM(ii.quantity),
                    0
                ) AS total_quantity

            FROM equipment_categories c

            LEFT JOIN inventory_items ii
                ON ii.category_id = c.id

            GROUP BY
                c.id,
                c.category_name

            ORDER BY
                total_quantity DESC,
                c.category_name ASC
        ");

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }
}


/* ============================================================
   FULL DASHBOARD STATISTICS
   ============================================================ */

/**
 * Return complete dashboard statistics.
 */
if (!function_exists('getDashboardStats')) {
    function getDashboardStats(PDO $pdo)
    {
        return [

            'total_players' =>
                getTotalPlayers($pdo),

            'active_players' =>
                getActivePlayers($pdo),

            'pending_clearance' =>
                getPendingClearanceCount($pdo),

            'cleared_players' =>
                getClearedPlayersCount($pdo),

            'total_items' =>
                getTotalInventoryItems($pdo),

            'total_stock' =>
                getTotalStock($pdo),

            'issued_items' =>
                getIssuedItemsCount($pdo),

            'total_teams' =>
                getTotalTeams($pdo),

            'low_stock_items' =>
                count(
                    getLowStockItems($pdo)
                ),

            'overdue_items' =>
                getOverdueIssuesCount($pdo)
        ];
    }
}


/* ============================================================
   DATABASE HEALTH HELPERS
   ============================================================ */

/**
 * Test PDO database connection.
 */
if (!function_exists('databaseConnectionIsAlive')) {
    function databaseConnectionIsAlive(PDO $pdo)
    {
        try {

            $pdo->query('SELECT 1');

            return true;

        } catch (PDOException $e) {

            error_log(
                'Database connection test failed: ' .
                $e->getMessage()
            );

            return false;
        }
    }
}


/**
 * Get database server version.
 */
if (!function_exists('getDatabaseVersion')) {
    function getDatabaseVersion(PDO $pdo)
    {
        try {

            return $pdo
                ->query('SELECT VERSION()')
                ->fetchColumn();

        } catch (PDOException $e) {

            error_log(
                'Database version error: ' .
                $e->getMessage()
            );

            return null;
        }
    }
}


/* ============================================================
   END OF FUNCTIONS.PHP
   ============================================================ */