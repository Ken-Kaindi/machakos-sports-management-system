/*==========================================================
 MODULE 02
 USERS & AUTHENTICATION
 Machakos University Sports Management System V2.0
==========================================================*/

USE machakos_sports_v2;

-- =========================================================
-- ROLES
-- =========================================================

CREATE TABLE roles (

    id INT AUTO_INCREMENT PRIMARY KEY,

    role_name VARCHAR(50) NOT NULL UNIQUE,

    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);

-- =========================================================
-- DEFAULT ROLES
-- =========================================================

INSERT INTO roles
(
    role_name,
    description
)
VALUES
(
    'Administrator',
    'Full system access'
),
(
    'Sports Officer',
    'Manage players, sports operations and equipment'
),
(
    'Coach',
    'Manage teams, players and training activities'
),
(
    'Store Keeper',
    'Manage inventory, equipment issuing and returns'
);

-- =========================================================
-- USERS
-- =========================================================

CREATE TABLE users (

    id INT AUTO_INCREMENT PRIMARY KEY,

    role_id INT NOT NULL,

    username VARCHAR(50) NOT NULL UNIQUE,

    password VARCHAR(255) NOT NULL,

    full_name VARCHAR(100) NOT NULL,

    email VARCHAR(100) UNIQUE,

    phone VARCHAR(20),

    profile_photo VARCHAR(255)
        DEFAULT 'default.png',

    is_active BOOLEAN DEFAULT TRUE,

    failed_attempts INT DEFAULT 0,

    last_login DATETIME NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_user_role
        FOREIGN KEY(role_id)
        REFERENCES roles(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT

);

-- =========================================================
-- LOGIN ATTEMPTS
-- =========================================================

CREATE TABLE login_attempts (

    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NULL,

    username VARCHAR(50),

    ip_address VARCHAR(45),

    browser TEXT,

    status ENUM(
        'SUCCESS',
        'FAILED'
    ) NOT NULL,

    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_login_user
        FOREIGN KEY(user_id)
        REFERENCES users(id)
        ON DELETE SET NULL

);

-- =========================================================
-- REMEMBER ME TOKENS
-- =========================================================

CREATE TABLE remember_tokens (

    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    selector CHAR(24) NOT NULL UNIQUE,

    validator_hash CHAR(64) NOT NULL,

    expires_at DATETIME NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_token_user
        FOREIGN KEY(user_id)
        REFERENCES users(id)
        ON DELETE CASCADE

);

-- =========================================================
-- ACTIVITY LOG
-- =========================================================

CREATE TABLE activity_log (

    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NULL,

    action VARCHAR(100) NOT NULL,

    description TEXT,

    ip_address VARCHAR(45),

    browser TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_activity_user
        FOREIGN KEY(user_id)
        REFERENCES users(id)
        ON DELETE SET NULL

);

-- =========================================================
-- INDEXES
-- =========================================================

CREATE INDEX idx_users_username
ON users(username);

CREATE INDEX idx_users_email
ON users(email);

CREATE INDEX idx_login_user
ON login_attempts(user_id);

CREATE INDEX idx_login_time
ON login_attempts(attempt_time);

CREATE INDEX idx_activity_user
ON activity_log(user_id);

CREATE INDEX idx_activity_time
ON activity_log(created_at);

CREATE INDEX idx_token_expiry
ON remember_tokens(expires_at);

-- =========================================================
-- MODULE 02 COMPLETE
-- Seed users are inserted by Module 10.
-- =========================================================
