/*==========================================================
 MODULE 03
 PLAYERS MANAGEMENT
 Machakos University Sports Management System V2.0
==========================================================*/

USE machakos_sports_v2;

-- =========================================================
-- PLAYERS
-- =========================================================

CREATE TABLE players (

    id INT AUTO_INCREMENT PRIMARY KEY,

    student_id VARCHAR(30) NOT NULL UNIQUE,

    registration_no VARCHAR(30) UNIQUE,

    first_name VARCHAR(50) NOT NULL,

    middle_name VARCHAR(50) NULL,

    last_name VARCHAR(50) NOT NULL,

    full_name VARCHAR(150) NOT NULL,

    gender ENUM(
        'Male',
        'Female'
    ) NOT NULL,

    date_of_birth DATE NOT NULL,

    national_id VARCHAR(20),

    phone VARCHAR(20),

    email VARCHAR(100),

    address TEXT,

    emergency_contact VARCHAR(100),

    emergency_phone VARCHAR(20),

    blood_group ENUM(
        'A+',
        'A-',
        'B+',
        'B-',
        'AB+',
        'AB-',
        'O+',
        'O-'
    ),

    allergies TEXT,

    faculty VARCHAR(100) NOT NULL,

    department VARCHAR(100),

    course VARCHAR(100) NOT NULL,

    year_of_study INT NOT NULL,

    team VARCHAR(80) NOT NULL,

    playing_position VARCHAR(80),

    admission_date DATE,

    registration_date TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP,

    profile_photo VARCHAR(255)
        DEFAULT 'default.png',

    status ENUM(
        'Active',
        'Inactive',
        'Suspended',
        'Graduated'
    )
        DEFAULT 'Active',

    created_by INT NULL,

    updated_by INT NULL,

    created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_player_creator
        FOREIGN KEY(created_by)
        REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    CONSTRAINT fk_player_updater
        FOREIGN KEY(updated_by)
        REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE

);

-- =========================================================
-- INDEXES
-- =========================================================

CREATE INDEX idx_player_student
ON players(student_id);

CREATE INDEX idx_player_registration
ON players(registration_no);

CREATE INDEX idx_player_name
ON players(full_name);

CREATE INDEX idx_player_team
ON players(team);

CREATE INDEX idx_player_faculty
ON players(faculty);

CREATE INDEX idx_player_department
ON players(department);

CREATE INDEX idx_player_course
ON players(course);

CREATE INDEX idx_player_year
ON players(year_of_study);

CREATE INDEX idx_player_status
ON players(status);

CREATE INDEX idx_player_phone
ON players(phone);

CREATE INDEX idx_player_email
ON players(email);

CREATE INDEX idx_player_created_by
ON players(created_by);

-- =========================================================
-- MODULE 03 COMPLETE
-- Seed players are inserted by Module 10.
-- =========================================================