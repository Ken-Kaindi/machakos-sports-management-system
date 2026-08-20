/*==========================================================
 MODULE 06
 PLAYER CLEARANCE MANAGEMENT
 Machakos University Sports Management System V2.0
==========================================================*/

USE machakos_sports_v2;

-- =========================================================
-- PLAYER CLEARANCE
-- =========================================================

CREATE TABLE player_clearance (

    id INT AUTO_INCREMENT PRIMARY KEY,

    player_id INT NOT NULL UNIQUE,

    outstanding_items INT NOT NULL DEFAULT 0,

    returned_items INT NOT NULL DEFAULT 0,

    lost_items INT NOT NULL DEFAULT 0,

    damaged_items INT NOT NULL DEFAULT 0,

    total_fine DECIMAL(12,2) NOT NULL DEFAULT 0.00,

    clearance_status ENUM(
        'Pending',
        'Cleared'
    ) NOT NULL DEFAULT 'Pending',

    approved_by INT NULL,

    approval_date DATETIME NULL,

    remarks TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_clearance_player
        FOREIGN KEY (player_id)
        REFERENCES players(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_clearance_user
        FOREIGN KEY (approved_by)
        REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE

);

-- =========================================================
-- INDEXES
-- =========================================================

CREATE INDEX idx_clearance_status
ON player_clearance(clearance_status);

CREATE INDEX idx_clearance_approved_by
ON player_clearance(approved_by);

-- =========================================================
-- PLAYER CLEARANCE SUMMARY VIEW
-- =========================================================
--
-- Calculates the current equipment position of every player.
--
-- Important:
-- Issue and return aggregations are performed independently
-- to prevent duplicate totals caused by multiple return records.
--
-- Clearance follows the application logic:
-- A player is Pending when outstanding equipment exists.
-- Fines do not automatically prevent clearance.
-- =========================================================

CREATE OR REPLACE VIEW view_player_clearance AS

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

    COALESCE(issue_data.total_issued, 0)
        AS total_issued,

    COALESCE(issue_data.outstanding_items, 0)
        AS outstanding_items,

    COALESCE(issue_data.returned_items, 0)
        AS returned_items,

    COALESCE(issue_data.lost_items, 0)
        AS lost_items,

    COALESCE(issue_data.damaged_items, 0)
        AS damaged_items,

    COALESCE(return_data.total_fine, 0.00)
        AS total_fine,

    CASE

        WHEN COALESCE(
            issue_data.outstanding_items,
            0
        ) > 0

        THEN 'Pending'

        ELSE 'Cleared'

    END AS calculated_clearance_status

FROM players p

LEFT JOIN
(
    SELECT

        player_id,

        COALESCE(
            SUM(quantity),
            0
        ) AS total_issued,

        COALESCE(
            SUM(
                CASE
                    WHEN status IN ('Issued','Overdue')
                    THEN quantity
                    ELSE 0
                END
            ),
            0
        ) AS outstanding_items,

        COALESCE(
            SUM(
                CASE
                    WHEN status = 'Returned'
                    THEN quantity
                    ELSE 0
                END
            ),
            0
        ) AS returned_items,

        COALESCE(
            SUM(
                CASE
                    WHEN status = 'Lost'
                    THEN quantity
                    ELSE 0
                END
            ),
            0
        ) AS lost_items,

        COALESCE(
            SUM(
                CASE
                    WHEN status = 'Damaged'
                    THEN quantity
                    ELSE 0
                END
            ),
            0
        ) AS damaged_items

    FROM equipment_issues

    GROUP BY player_id

) issue_data

    ON p.id = issue_data.player_id

LEFT JOIN
(
    SELECT

        ei.player_id,

        COALESCE(
            SUM(
                COALESCE(er.fine_amount, 0.00)
            ),
            0.00
        ) AS total_fine

    FROM equipment_issues ei

    INNER JOIN equipment_returns er
        ON ei.id = er.issue_id

    GROUP BY ei.player_id

) return_data

    ON p.id = return_data.player_id;

-- =========================================================
-- PROCEDURE
-- PLAYERS READY FOR CLEARANCE
-- =========================================================

DROP PROCEDURE IF EXISTS sp_cleared_players;

DELIMITER $$

CREATE PROCEDURE sp_cleared_players()

BEGIN

    SELECT *

    FROM view_player_clearance

    WHERE calculated_clearance_status = 'Cleared'

    ORDER BY full_name ASC;

END$$

DELIMITER ;

-- =========================================================
-- PROCEDURE
-- PLAYERS WITH PENDING CLEARANCE
-- =========================================================

DROP PROCEDURE IF EXISTS sp_pending_clearance;

DELIMITER $$

CREATE PROCEDURE sp_pending_clearance()

BEGIN

    SELECT *

    FROM view_player_clearance

    WHERE calculated_clearance_status = 'Pending'

    ORDER BY full_name ASC;

END$$

DELIMITER ;

-- =========================================================
-- PROCEDURE
-- CLEARANCE STATISTICS
-- =========================================================

DROP PROCEDURE IF EXISTS sp_clearance_statistics;

DELIMITER $$

CREATE PROCEDURE sp_clearance_statistics()

BEGIN

    SELECT

        COUNT(*) AS total_players,

        COALESCE(
            SUM(
                CASE
                    WHEN calculated_clearance_status = 'Cleared'
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS cleared_players,

        COALESCE(
            SUM(
                CASE
                    WHEN calculated_clearance_status = 'Pending'
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS pending_players

    FROM view_player_clearance;

END$$

DELIMITER ;

-- =========================================================
-- MODULE 06 COMPLETE
--
-- Clearance seed records are inserted by Module 10.
-- =========================================================