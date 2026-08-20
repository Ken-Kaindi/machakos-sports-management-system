/*==========================================================
 MODULE 08
 REPORTS & DASHBOARD
 Machakos University Sports Management System V2.0
==========================================================*/

USE machakos_sports_v2;

-- =========================================================
-- DASHBOARD SUMMARY
-- =========================================================

CREATE OR REPLACE VIEW view_dashboard_summary AS

SELECT

    /* Active system users */
    (
        SELECT COUNT(*)
        FROM users
        WHERE is_active = 1
    ) AS total_users,

    /* All non-graduated players */
    (
        SELECT COUNT(*)
        FROM players
        WHERE status <> 'Graduated'
    ) AS total_players,

    /* Currently active players */
    (
        SELECT COUNT(*)
        FROM players
        WHERE status = 'Active'
    ) AS active_players,

    /* Inventory records excluding disposed items */
    (
        SELECT COUNT(*)
        FROM inventory_items
        WHERE status <> 'Disposed'
    ) AS total_inventory_items,

    /* Current physical stock */
    (
        SELECT COALESCE(
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
    ) AS total_stock,

    /* Equipment currently issued or overdue */
    (
        SELECT COALESCE(
            SUM(quantity),
            0
        )
        FROM equipment_issues
        WHERE status IN ('Issued', 'Overdue')
    ) AS equipment_issued,

    /* Total quantity returned */
    (
        SELECT COALESCE(
            SUM(quantity_returned),
            0
        )
        FROM equipment_returns
    ) AS equipment_returned,

    /* Total tournaments */
    (
        SELECT COUNT(*)
        FROM tournaments
    ) AS tournaments,

    /* Pending clearance */
    (
        SELECT COUNT(*)
        FROM view_player_clearance
        WHERE calculated_clearance_status = 'Pending'
    ) AS pending_clearance,

    /* Cleared players */
    (
        SELECT COUNT(*)
        FROM view_player_clearance
        WHERE calculated_clearance_status = 'Cleared'
    ) AS cleared_players;


-- =========================================================
-- INVENTORY REPORT
-- =========================================================

CREATE OR REPLACE VIEW view_inventory_report AS

SELECT

    i.id,

    i.asset_number,

    i.item_name,

    c.category_name,

    s.supplier_name,

    i.quantity,

    i.minimum_stock,

    i.unit,

    i.purchase_price,

    i.replacement_cost,

    (
        i.quantity * i.purchase_price
    ) AS inventory_value,

    i.item_condition,

    i.status,

    i.storage_location,

    CASE

        WHEN i.status IN ('Disposed', 'Lost')
            THEN 'NOT AVAILABLE'

        WHEN i.quantity <= i.minimum_stock
            THEN 'LOW STOCK'

        ELSE 'NORMAL'

    END AS stock_status

FROM inventory_items i

LEFT JOIN equipment_categories c
    ON i.category_id = c.id

LEFT JOIN suppliers s
    ON i.supplier_id = s.id;


-- =========================================================
-- CURRENT EQUIPMENT ISSUE REPORT
-- =========================================================

CREATE OR REPLACE VIEW view_equipment_issue_report AS

SELECT

    ei.id,

    p.student_id,

    p.registration_no,

    p.full_name,

    p.team,

    i.asset_number,

    i.item_name,

    i.brand,

    i.model,

    ei.quantity,

    ei.issue_date,

    ei.expected_return_date,

    ei.status,

    u.full_name AS issued_by

FROM equipment_issues ei

INNER JOIN players p
    ON ei.player_id = p.id

INNER JOIN inventory_items i
    ON ei.inventory_id = i.id

LEFT JOIN users u
    ON ei.issued_by = u.id

WHERE ei.status IN ('Issued', 'Overdue');


-- =========================================================
-- PLAYER CLEARANCE REPORT
-- =========================================================
--
-- Uses the calculated clearance view from Module 06.
-- =========================================================

CREATE OR REPLACE VIEW view_clearance_report AS

SELECT

    pc.id AS clearance_id,

    pc.player_id,

    p.student_id,

    p.registration_no,

    p.full_name,

    p.team,

    p.faculty,

    p.department,

    p.course,

    p.year_of_study,

    pc.outstanding_items,

    pc.returned_items,

    pc.lost_items,

    pc.damaged_items,

    pc.total_fine,

    pc.clearance_status
        AS recorded_clearance_status,

    vpc.calculated_clearance_status,

    pc.approved_by,

    pc.approval_date,

    pc.remarks,

    pc.created_at,

    pc.updated_at

FROM player_clearance pc

INNER JOIN players p
    ON pc.player_id = p.id

LEFT JOIN view_player_clearance vpc
    ON pc.player_id = vpc.id;


-- =========================================================
-- TOURNAMENT REPORT
-- =========================================================

CREATE OR REPLACE VIEW view_tournament_report AS

SELECT

    t.id,

    t.tournament_name,

    t.sport,

    t.season,

    t.venue,

    t.start_date,

    t.end_date,

    t.status,

    COUNT(
        CASE
            WHEN tr.status = 'Registered'
            THEN tr.id
        END
    ) AS registered_players

FROM tournaments t

LEFT JOIN tournament_registrations tr
    ON t.id = tr.tournament_id

GROUP BY

    t.id,
    t.tournament_name,
    t.sport,
    t.season,
    t.venue,
    t.start_date,
    t.end_date,
    t.status;


-- =========================================================
-- LOW STOCK REPORT
-- =========================================================

CREATE OR REPLACE VIEW view_low_stock AS

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

WHERE quantity <= minimum_stock

  AND status NOT IN (
      'Disposed',
      'Lost'
  );


-- =========================================================
-- INVENTORY VALUE PROCEDURE
-- =========================================================

DROP PROCEDURE IF EXISTS sp_inventory_value;

DELIMITER $$

CREATE PROCEDURE sp_inventory_value()

BEGIN

    SELECT

        COALESCE(
            SUM(
                CASE
                    WHEN status <> 'Disposed'
                    THEN quantity * purchase_price
                    ELSE 0
                END
            ),
            0
        ) AS total_inventory_value

    FROM inventory_items;

END$$

DELIMITER ;


-- =========================================================
-- PLAYER STATISTICS PROCEDURE
-- =========================================================

DROP PROCEDURE IF EXISTS sp_player_statistics;

DELIMITER $$

CREATE PROCEDURE sp_player_statistics()

BEGIN

    SELECT

        team,

        COUNT(*) AS total_players

    FROM players

    WHERE status = 'Active'

    GROUP BY team

    ORDER BY total_players DESC;

END$$

DELIMITER ;


-- =========================================================
-- DASHBOARD PROCEDURE
-- =========================================================

DROP PROCEDURE IF EXISTS sp_dashboard;

DELIMITER $$

CREATE PROCEDURE sp_dashboard()

BEGIN

    SELECT *

    FROM view_dashboard_summary;

END$$

DELIMITER ;


-- =========================================================
-- MODULE 08 COMPLETE
-- =========================================================