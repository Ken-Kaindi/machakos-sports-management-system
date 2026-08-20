/*==========================================================
 MODULE 05
 EQUIPMENT ISSUING & RETURNS
 Machakos University Sports Management System V2.0
==========================================================*/

USE machakos_sports_v2;

-- =========================================================
-- EQUIPMENT ISSUE TRANSACTIONS
-- =========================================================

CREATE TABLE equipment_issues (

    id INT AUTO_INCREMENT PRIMARY KEY,

    player_id INT NOT NULL,

    inventory_id INT NOT NULL,

    quantity INT NOT NULL DEFAULT 1,

    issue_date DATETIME DEFAULT CURRENT_TIMESTAMP,

    expected_return_date DATE NULL,

    issued_by INT NOT NULL,

    status ENUM(
        'Issued',
        'Returned',
        'Lost',
        'Damaged',
        'Overdue'
    )
    DEFAULT 'Issued',

    remarks TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_issue_player
        FOREIGN KEY(player_id)
        REFERENCES players(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_issue_inventory
        FOREIGN KEY(inventory_id)
        REFERENCES inventory_items(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_issue_user
        FOREIGN KEY(issued_by)
        REFERENCES users(id)
        ON DELETE RESTRICT

);

-- =========================================================
-- EQUIPMENT RETURNS
-- =========================================================

CREATE TABLE equipment_returns (

    id INT AUTO_INCREMENT PRIMARY KEY,

    issue_id INT NOT NULL,

    returned_by INT NOT NULL,

    return_date DATETIME DEFAULT CURRENT_TIMESTAMP,

    quantity_returned INT NOT NULL DEFAULT 1,

    condition_status ENUM(
        'Excellent',
        'Good',
        'Fair',
        'Poor',
        'Damaged',
        'Lost'
    )
    DEFAULT 'Good',

    remarks TEXT,

    fine_amount DECIMAL(10,2) DEFAULT 0.00,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_return_issue
        FOREIGN KEY(issue_id)
        REFERENCES equipment_issues(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_return_user
        FOREIGN KEY(returned_by)
        REFERENCES users(id)
        ON DELETE RESTRICT

);

-- =========================================================
-- LOST / DAMAGED EQUIPMENT INCIDENTS
-- =========================================================

CREATE TABLE equipment_incidents (

    id INT AUTO_INCREMENT PRIMARY KEY,

    issue_id INT NOT NULL,

    incident_type ENUM(
        'Lost',
        'Damaged'
    ) NOT NULL,

    incident_date DATE DEFAULT (CURRENT_DATE),

    description TEXT,

    replacement_cost DECIMAL(12,2) DEFAULT 0.00,

    amount_paid DECIMAL(12,2) DEFAULT 0.00,

    payment_status ENUM(
        'Pending',
        'Partial',
        'Paid'
    )
    DEFAULT 'Pending',

    recorded_by INT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_incident_issue
        FOREIGN KEY(issue_id)
        REFERENCES equipment_issues(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_incident_user
        FOREIGN KEY(recorded_by)
        REFERENCES users(id)
        ON DELETE SET NULL

);

-- =========================================================
-- INDEXES
-- =========================================================

CREATE INDEX idx_issue_player
ON equipment_issues(player_id);

CREATE INDEX idx_issue_inventory
ON equipment_issues(inventory_id);

CREATE INDEX idx_issue_user
ON equipment_issues(issued_by);

CREATE INDEX idx_issue_status
ON equipment_issues(status);

CREATE INDEX idx_issue_date
ON equipment_issues(issue_date);

CREATE INDEX idx_return_issue
ON equipment_returns(issue_id);

CREATE INDEX idx_return_user
ON equipment_returns(returned_by);

CREATE INDEX idx_incident_issue
ON equipment_incidents(issue_id);

CREATE INDEX idx_incident_user
ON equipment_incidents(recorded_by);

-- =========================================================
-- CURRENT ISSUED EQUIPMENT VIEW
-- =========================================================

CREATE OR REPLACE VIEW view_current_issues AS

SELECT

    ei.id,

    p.student_id,

    p.full_name,

    i.asset_number,

    i.item_name,

    ei.quantity,

    ei.issue_date,

    ei.expected_return_date,

    ei.status

FROM equipment_issues ei

INNER JOIN players p
    ON ei.player_id = p.id

INNER JOIN inventory_items i
    ON ei.inventory_id = i.id

WHERE ei.status = 'Issued';

-- =========================================================
-- MODULE 05 COMPLETE
--
-- IMPORTANT:
-- player_clearance belongs to Module 06.
-- Equipment triggers belong to Module 09.
-- Seed equipment transactions belong to Module 10.
-- =========================================================