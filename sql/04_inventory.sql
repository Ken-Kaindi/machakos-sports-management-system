/*==========================================================
 MODULE 04
 INVENTORY MANAGEMENT
 Machakos University Sports Management System V2.0
==========================================================*/

USE machakos_sports_v2;

-- =========================================================
-- EQUIPMENT CATEGORIES
-- =========================================================

CREATE TABLE equipment_categories (

    id INT AUTO_INCREMENT PRIMARY KEY,

    category_name VARCHAR(100) NOT NULL UNIQUE,

    description TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);

-- =========================================================
-- SUPPLIERS
-- =========================================================

CREATE TABLE suppliers (

    id INT AUTO_INCREMENT PRIMARY KEY,

    supplier_name VARCHAR(150) NOT NULL,

    contact_person VARCHAR(100),

    phone VARCHAR(30),

    email VARCHAR(120),

    address TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);

-- =========================================================
-- INVENTORY ITEMS
-- =========================================================

CREATE TABLE inventory_items (

    id INT AUTO_INCREMENT PRIMARY KEY,

    category_id INT NOT NULL,

    supplier_id INT NULL,

    asset_number VARCHAR(60) NOT NULL UNIQUE,

    barcode VARCHAR(100),

    qr_code VARCHAR(255),

    serial_number VARCHAR(100),

    item_name VARCHAR(150) NOT NULL,

    description TEXT,

    brand VARCHAR(100),

    model VARCHAR(100),

    quantity INT NOT NULL DEFAULT 0,

    minimum_stock INT NOT NULL DEFAULT 5,

    unit VARCHAR(30) DEFAULT 'Piece',

    purchase_price DECIMAL(12,2) DEFAULT 0.00,

    replacement_cost DECIMAL(12,2) DEFAULT 0.00,

    purchase_date DATE,

    item_condition ENUM(
        'New',
        'Excellent',
        'Good',
        'Fair',
        'Poor',
        'Damaged'
    )
    DEFAULT 'Good',

    status ENUM(
        'Available',
        'Issued',
        'Maintenance',
        'Lost',
        'Disposed'
    )
    DEFAULT 'Available',

    storage_location VARCHAR(120),

    remarks TEXT,

    created_by INT NULL,

    updated_by INT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_inventory_category
        FOREIGN KEY(category_id)
        REFERENCES equipment_categories(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_inventory_supplier
        FOREIGN KEY(supplier_id)
        REFERENCES suppliers(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_inventory_creator
        FOREIGN KEY(created_by)
        REFERENCES users(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_inventory_updater
        FOREIGN KEY(updated_by)
        REFERENCES users(id)
        ON DELETE SET NULL

);

-- =========================================================
-- INDEXES
-- =========================================================

CREATE INDEX idx_inventory_name
ON inventory_items(item_name);

CREATE INDEX idx_inventory_category
ON inventory_items(category_id);

CREATE INDEX idx_inventory_status
ON inventory_items(status);

CREATE INDEX idx_inventory_condition
ON inventory_items(item_condition);

CREATE INDEX idx_inventory_supplier
ON inventory_items(supplier_id);

CREATE INDEX idx_inventory_barcode
ON inventory_items(barcode);

CREATE INDEX idx_inventory_created_by
ON inventory_items(created_by);

-- =========================================================
-- MODULE 04 COMPLETE
-- Seed inventory is inserted by Module 10.
-- =========================================================