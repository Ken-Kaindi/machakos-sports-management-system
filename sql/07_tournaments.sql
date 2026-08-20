/*==========================================================
 MODULE 07
 TOURNAMENT MANAGEMENT
 Machakos University Sports Management System V2.0
==========================================================*/

USE machakos_sports_v2;

-- =========================================================
-- TOURNAMENTS
-- =========================================================

CREATE TABLE tournaments (

    id INT AUTO_INCREMENT PRIMARY KEY,

    tournament_name VARCHAR(150) NOT NULL,

    sport VARCHAR(100) NOT NULL,

    season VARCHAR(50),

    venue VARCHAR(150),

    start_date DATE,

    end_date DATE,

    status ENUM(
        'Upcoming',
        'Registration Open',
        'Ongoing',
        'Completed',
        'Cancelled'
    )
    DEFAULT 'Upcoming',

    description TEXT,

    created_by INT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_tournament_creator
        FOREIGN KEY(created_by)
        REFERENCES users(id)
        ON DELETE SET NULL

);

-- =========================================================
-- TOURNAMENT PLAYER REGISTRATIONS
-- =========================================================

CREATE TABLE tournament_registrations (

    id INT AUTO_INCREMENT PRIMARY KEY,

    tournament_id INT NOT NULL,

    player_id INT NOT NULL,

    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    status ENUM(
        'Registered',
        'Withdrawn'
    )
    DEFAULT 'Registered',

    UNIQUE KEY uq_tournament_player
        (tournament_id, player_id),

    CONSTRAINT fk_registration_tournament
        FOREIGN KEY(tournament_id)
        REFERENCES tournaments(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_registration_player
        FOREIGN KEY(player_id)
        REFERENCES players(id)
        ON DELETE CASCADE

);

-- =========================================================
-- TOURNAMENT FIXTURES
-- =========================================================

CREATE TABLE tournament_fixtures (

    id INT AUTO_INCREMENT PRIMARY KEY,

    tournament_id INT NOT NULL,

    home_team VARCHAR(100) NOT NULL,

    away_team VARCHAR(100) NOT NULL,

    fixture_date DATETIME NOT NULL,

    venue VARCHAR(150),

    referee VARCHAR(100),

    status ENUM(
        'Scheduled',
        'Postponed',
        'Completed',
        'Cancelled'
    )
    DEFAULT 'Scheduled',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_fixture_tournament
        FOREIGN KEY(tournament_id)
        REFERENCES tournaments(id)
        ON DELETE CASCADE

);

-- =========================================================
-- MATCH RESULTS
-- =========================================================

CREATE TABLE match_results (

    id INT AUTO_INCREMENT PRIMARY KEY,

    fixture_id INT NOT NULL UNIQUE,

    home_score INT NOT NULL DEFAULT 0,

    away_score INT NOT NULL DEFAULT 0,

    winner VARCHAR(100),

    remarks TEXT,

    recorded_by INT NULL,

    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_result_fixture
        FOREIGN KEY(fixture_id)
        REFERENCES tournament_fixtures(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_result_user
        FOREIGN KEY(recorded_by)
        REFERENCES users(id)
        ON DELETE SET NULL

);

-- =========================================================
-- INDEXES
-- =========================================================

CREATE INDEX idx_tournament_status
ON tournaments(status);

CREATE INDEX idx_tournament_sport
ON tournaments(sport);

CREATE INDEX idx_tournament_dates
ON tournaments(start_date, end_date);

CREATE INDEX idx_tournament_creator
ON tournaments(created_by);

CREATE INDEX idx_registration_player
ON tournament_registrations(player_id);

CREATE INDEX idx_registration_status
ON tournament_registrations(status);

CREATE INDEX idx_fixture_tournament
ON tournament_fixtures(tournament_id);

CREATE INDEX idx_fixture_date
ON tournament_fixtures(fixture_date);

CREATE INDEX idx_fixture_status
ON tournament_fixtures(status);

CREATE INDEX idx_result_recorded_by
ON match_results(recorded_by);

-- =========================================================
-- MODULE 07 COMPLETE
-- Seed tournament data is inserted by Module 10.
-- =========================================================