/*==========================================================
 MODULE 09
 DATABASE AUTOMATION
 Machakos University Sports Management System V2.0
==========================================================*/

USE machakos_sports_v2;

-- =========================================================
-- REMOVE EXISTING AUTOMATION OBJECTS
-- =========================================================

DROP TRIGGER IF EXISTS trg_issue_equipment;
DROP TRIGGER IF EXISTS trg_return_equipment;
DROP TRIGGER IF EXISTS trg_log_new_player;
DROP TRIGGER IF EXISTS trg_update_last_login;

DROP PROCEDURE IF EXISTS sp_low_stock;
DROP PROCEDURE IF EXISTS sp_tournament_summary;

DROP FUNCTION IF EXISTS fn_player_age;

DROP EVENT IF EXISTS ev_delete_expired_tokens;
DROP EVENT IF EXISTS ev_cleanup_activity_log;

-- =========================================================
-- TRIGGER
-- REDUCE STOCK AFTER EQUIPMENT ISSUE
-- =========================================================
--
-- The inventory quantity is reduced automatically whenever
-- equipment is issued.
--
-- The CASE uses the quantity after subtraction.
-- =========================================================

DELIMITER $$

CREATE TRIGGER trg_issue_equipment

AFTER INSERT
ON equipment_issues

FOR EACH ROW

BEGIN

    UPDATE inventory_items

    SET quantity = quantity - NEW.quantity,

        status =
            CASE
                WHEN quantity - NEW.quantity <= 0
                    THEN 'Issued'
                ELSE status
            END

    WHERE id = NEW.inventory_id;

END$$

DELIMITER ;

-- =========================================================
-- TRIGGER
-- RETURN STOCK AFTER EQUIPMENT RETURN
-- =========================================================
--
-- Lost and damaged equipment must NOT be returned to
-- usable inventory stock.
--
-- Only normal usable returns increase inventory quantity.
-- =========================================================

DELIMITER $$

CREATE TRIGGER trg_return_equipment

AFTER INSERT
ON equipment_returns

FOR EACH ROW

BEGIN

    DECLARE itemID INT;

    SELECT inventory_id

    INTO itemID

    FROM equipment_issues

    WHERE id = NEW.issue_id;

    -- Return usable equipment to stock.
    IF NEW.condition_status IN
        ('Excellent', 'Good', 'Fair', 'Poor') THEN

        UPDATE inventory_items

        SET quantity = quantity + NEW.quantity_returned,

            status = 'Available'

        WHERE id = itemID;

    END IF;

    -- Update equipment issue status.
    UPDATE equipment_issues

    SET status =
        CASE

            WHEN NEW.condition_status = 'Lost'
                THEN 'Lost'

            WHEN NEW.condition_status = 'Damaged'
                THEN 'Damaged'

            ELSE 'Returned'

        END

    WHERE id = NEW.issue_id;

END$$

DELIMITER ;

-- =========================================================
-- TRIGGER
-- LOG NEW PLAYER
-- =========================================================

DELIMITER $$

CREATE TRIGGER trg_log_new_player

AFTER INSERT
ON players

FOR EACH ROW

BEGIN

    IF NEW.created_by IS NOT NULL THEN

        INSERT INTO activity_log
        (
            user_id,
            action,
            description,
            created_at
        )

        VALUES
        (
            NEW.created_by,
            'PLAYER_REGISTERED',
            CONCAT(
                'Registered player ',
                NEW.full_name
            ),
            NOW()
        );

    END IF;

END$$

DELIMITER ;

-- =========================================================
-- TRIGGER
-- UPDATE LAST LOGIN
-- =========================================================

DELIMITER $$

CREATE TRIGGER trg_update_last_login

AFTER INSERT
ON login_attempts

FOR EACH ROW

BEGIN

    IF NEW.status = 'SUCCESS'
       AND NEW.user_id IS NOT NULL THEN

        UPDATE users

        SET last_login = NOW()

        WHERE id = NEW.user_id;

    END IF;

END$$

DELIMITER ;

-- =========================================================
-- LOW STOCK PROCEDURE
-- =========================================================

DELIMITER $$

CREATE PROCEDURE sp_low_stock()

BEGIN

    SELECT *

    FROM inventory_items

    WHERE quantity <= minimum_stock

    ORDER BY quantity ASC;

END$$

DELIMITER ;

-- =========================================================
-- TOURNAMENT SUMMARY PROCEDURE
-- =========================================================

DELIMITER $$

CREATE PROCEDURE sp_tournament_summary()

BEGIN

    SELECT

        sport,

        COUNT(*) AS tournaments

    FROM tournaments

    GROUP BY sport

    ORDER BY tournaments DESC;

END$$

DELIMITER ;

-- =========================================================
-- PLAYER AGE FUNCTION
-- =========================================================

DELIMITER $$

CREATE FUNCTION fn_player_age(
    dob DATE
)

RETURNS INT

DETERMINISTIC

BEGIN

    RETURN TIMESTAMPDIFF(
        YEAR,
        dob,
        CURDATE()
    );

END$$

DELIMITER ;

-- =========================================================
-- EXPIRED REMEMBER TOKEN CLEANUP EVENT
-- =========================================================
--
-- This event is created but does not force the global
-- event scheduler to be enabled.
--
-- Enable the scheduler separately when required:
--
-- SET GLOBAL event_scheduler = ON;
--
-- =========================================================

DELIMITER $$

CREATE EVENT ev_delete_expired_tokens

ON SCHEDULE EVERY 1 DAY

DO

BEGIN

    DELETE FROM remember_tokens

    WHERE expires_at < NOW();

END$$

DELIMITER ;

-- =========================================================
-- ACTIVITY LOG CLEANUP EVENT
-- =========================================================

DELIMITER $$

CREATE EVENT ev_cleanup_activity_log

ON SCHEDULE EVERY 1 MONTH

DO

BEGIN

    DELETE FROM activity_log

    WHERE created_at <
        DATE_SUB(
            NOW(),
            INTERVAL 2 YEAR
        );

END$$

DELIMITER ;

-- =========================================================
-- MODULE 09 COMPLETE
-- =========================================================