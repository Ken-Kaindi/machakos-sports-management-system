/*==========================================================
 MODULE 10
 SEED DATA
 Machakos University Sports Management System V2.0
==========================================================*/

USE machakos_sports_v2;

-- =========================================================
-- DEFAULT USERS
--
-- Development password:
-- password
--
-- These credentials are for LOCAL DEVELOPMENT ONLY.
-- Change/remove them before production deployment.
-- =========================================================

INSERT INTO users
(
    role_id,
    username,
    password,
    full_name,
    email,
    phone
)

SELECT
    r.id,
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'System Administrator',
    'admin@machakos.ac.ke',
    '0700000000'

FROM roles r

WHERE r.role_name = 'Administrator'

AND NOT EXISTS (
    SELECT 1
    FROM users
    WHERE username = 'admin'
);

INSERT INTO users
(
    role_id,
    username,
    password,
    full_name,
    email,
    phone
)

SELECT
    r.id,
    'sports',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Sports Officer',
    'sports@machakos.ac.ke',
    '0711000000'

FROM roles r

WHERE r.role_name = 'Sports Officer'

AND NOT EXISTS (
    SELECT 1
    FROM users
    WHERE username = 'sports'
);

INSERT INTO users
(
    role_id,
    username,
    password,
    full_name,
    email,
    phone
)

SELECT
    r.id,
    'coach',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Head Coach',
    'coach@machakos.ac.ke',
    '0722000000'

FROM roles r

WHERE r.role_name = 'Coach'

AND NOT EXISTS (
    SELECT 1
    FROM users
    WHERE username = 'coach'
);

INSERT INTO users
(
    role_id,
    username,
    password,
    full_name,
    email,
    phone
)

SELECT
    r.id,
    'storekeeper',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Store Keeper',
    'store@machakos.ac.ke',
    '0733000000'

FROM roles r

WHERE r.role_name = 'Store Keeper'

AND NOT EXISTS (
    SELECT 1
    FROM users
    WHERE username = 'storekeeper'
);

-- =========================================================
-- SAMPLE PLAYERS
-- =========================================================

INSERT INTO players
(
    student_id,
    registration_no,
    first_name,
    middle_name,
    last_name,
    full_name,
    gender,
    date_of_birth,
    phone,
    email,
    faculty,
    department,
    course,
    year_of_study,
    team,
    playing_position,
    admission_date,
    created_by
)

VALUES

(
    'BBIT/001/2023',
    'REG001',
    'John',
    'Kamau',
    'Maina',
    'John Kamau Maina',
    'Male',
    '2003-05-15',
    '0712345678',
    'john@student.mksu.ac.ke',
    'School of Engineering',
    'Computer Science',
    'BSc Computer Science',
    3,
    'Football',
    'Striker',
    '2023-09-04',
    (
        SELECT id
        FROM users
        WHERE username = 'admin'
    )
),

(
    'BED/014/2022',
    'REG002',
    'Mary',
    'Wanjiku',
    'Njeri',
    'Mary Wanjiku Njeri',
    'Female',
    '2004-02-10',
    '0723456789',
    'mary@student.mksu.ac.ke',
    'School of Education',
    'Education',
    'Bachelor of Education',
    2,
    'Netball',
    'Goal Shooter',
    '2022-09-05',
    (
        SELECT id
        FROM users
        WHERE username = 'admin'
    )
),

(
    'BCOM/022/2021',
    'REG003',
    'James',
    'Kiptoo',
    'Rotich',
    'James Kiptoo Rotich',
    'Male',
    '2002-11-20',
    '0734567890',
    'james@student.mksu.ac.ke',
    'School of Business',
    'Accounting',
    'Bachelor of Commerce',
    4,
    'Basketball',
    'Point Guard',
    '2021-09-06',
    (
        SELECT id
        FROM users
        WHERE username = 'admin'
    )
),

(
    'BENG/011/2024',
    'REG004',
    'Faith',
    'Achieng',
    'Atieno',
    'Faith Achieng Atieno',
    'Female',
    '2005-07-30',
    '0745678901',
    'faith@student.mksu.ac.ke',
    'School of Engineering',
    'Civil Engineering',
    'BSc Civil Engineering',
    1,
    'Volleyball',
    'Setter',
    '2024-09-02',
    (
        SELECT id
        FROM users
        WHERE username = 'admin'
    )
),

(
    'BIT/010/2022',
    'REG005',
    'Brian',
    'Mutiso',
    'Kyalo',
    'Brian Mutiso Kyalo',
    'Male',
    '2003-01-12',
    '0756789012',
    'brian@student.mksu.ac.ke',
    'School of Computing',
    'Information Technology',
    'BSc Information Technology',
    2,
    'Rugby',
    'Fly Half',
    '2022-09-05',
    (
        SELECT id
        FROM users
        WHERE username = 'admin'
    )
);

-- =========================================================
-- EQUIPMENT CATEGORIES
-- =========================================================

INSERT INTO equipment_categories
(
    category_name,
    description
)

VALUES

(
    'Balls',
    'Footballs, basketballs, volleyballs and other balls'
),

(
    'Uniforms',
    'Sports jerseys and kits'
),

(
    'Footwear',
    'Sports shoes and boots'
),

(
    'Protective Gear',
    'Helmets, shin guards and safety equipment'
),

(
    'Training Equipment',
    'Cones, bibs, ladders and markers'
),

(
    'Gym Equipment',
    'Fitness equipment'
),

(
    'Medical Equipment',
    'First aid and rehabilitation equipment'
),

(
    'Electronics',
    'Electronic sports equipment'
),

(
    'Office Equipment',
    'Administrative equipment'
),

(
    'Other',
    'Miscellaneous inventory'
);

-- =========================================================
-- SUPPLIERS
-- =========================================================

INSERT INTO suppliers
(
    supplier_name,
    contact_person,
    phone,
    email,
    address
)

VALUES

(
    'Decathlon Kenya',
    'John Mwangi',
    '0700112233',
    'info@decathlon.co.ke',
    'Nairobi'
),

(
    'Adidas East Africa',
    'Peter Otieno',
    '0722334455',
    'orders@adidas.co.ke',
    'Nairobi'
),

(
    'Nike Kenya',
    'Grace Akinyi',
    '0711223344',
    'sales@nike.co.ke',
    'Nairobi'
),

(
    'Puma Kenya',
    'David Mutiso',
    '0733445566',
    'info@puma.co.ke',
    'Nairobi'
);

-- =========================================================
-- SAMPLE INVENTORY
-- =========================================================

INSERT INTO inventory_items
(
    category_id,
    supplier_id,
    asset_number,
    barcode,
    item_name,
    brand,
    quantity,
    minimum_stock,
    purchase_price,
    replacement_cost,
    purchase_date,
    item_condition,
    status,
    storage_location,
    created_by
)

VALUES

(
    1,
    1,
    'MKSU-000001',
    '100000001',
    'Football Match Ball',
    'Adidas',
    20,
    5,
    4500.00,
    5000.00,
    '2026-01-10',
    'Excellent',
    'Available',
    'Main Store',
    (
        SELECT id FROM users
        WHERE username = 'storekeeper'
    )
),

(
    1,
    1,
    'MKSU-000002',
    '100000002',
    'Training Football',
    'Molten',
    35,
    10,
    2500.00,
    3000.00,
    '2026-02-01',
    'Good',
    'Available',
    'Main Store',
    (
        SELECT id FROM users
        WHERE username = 'storekeeper'
    )
),

(
    2,
    2,
    'MKSU-000003',
    '100000003',
    'Football Jerseys',
    'Adidas',
    60,
    10,
    1800.00,
    2200.00,
    '2026-02-15',
    'Excellent',
    'Available',
    'Uniform Store',
    (
        SELECT id FROM users
        WHERE username = 'storekeeper'
    )
),

(
    3,
    3,
    'MKSU-000004',
    '100000004',
    'Football Boots',
    'Nike',
    40,
    8,
    9500.00,
    10500.00,
    '2026-03-05',
    'Excellent',
    'Available',
    'Equipment Room',
    (
        SELECT id FROM users
        WHERE username = 'storekeeper'
    )
),

(
    4,
    4,
    'MKSU-000005',
    '100000005',
    'Shin Guards',
    'Puma',
    50,
    10,
    1200.00,
    1500.00,
    '2026-03-20',
    'Good',
    'Available',
    'Equipment Room',
    (
        SELECT id FROM users
        WHERE username = 'storekeeper'
    )
),

(
    5,
    1,
    'MKSU-000006',
    '100000006',
    'Training Cones',
    'Generic',
    100,
    20,
    250.00,
    300.00,
    '2026-04-01',
    'Excellent',
    'Available',
    'Training Store',
    (
        SELECT id FROM users
        WHERE username = 'storekeeper'
    )
),

(
    7,
    1,
    'MKSU-000007',
    '100000007',
    'First Aid Kit',
    'St John',
    10,
    2,
    5000.00,
    6000.00,
    '2026-04-10',
    'Excellent',
    'Available',
    'Sports Office',
    (
        SELECT id FROM users
        WHERE username = 'storekeeper'
    )
);

-- =========================================================
-- SAMPLE EQUIPMENT ISSUES
-- =========================================================

INSERT INTO equipment_issues
(
    player_id,
    inventory_id,
    quantity,
    expected_return_date,
    issued_by,
    remarks
)

VALUES

(
    1,
    1,
    1,
    '2026-12-20',
    (
        SELECT id FROM users
        WHERE username = 'storekeeper'
    ),
    'Football training'
),

(
    2,
    5,
    1,
    '2026-11-30',
    (
        SELECT id FROM users
        WHERE username = 'storekeeper'
    ),
    'Tournament'
),

(
    3,
    7,
    1,
    '2026-12-05',
    (
        SELECT id FROM users
        WHERE username = 'storekeeper'
    ),
    'League match'
);

-- =========================================================
-- SAMPLE EQUIPMENT RETURN
-- =========================================================

INSERT INTO equipment_returns
(
    issue_id,
    returned_by,
    quantity_returned,
    condition_status,
    remarks,
    fine_amount
)

VALUES

(
    1,
    (
        SELECT id FROM users
        WHERE username = 'storekeeper'
    ),
    1,
    'Good',
    'Returned after training',
    0.00
);

-- =========================================================
-- SAMPLE CLEARANCE RECORDS
-- =========================================================

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
    1,
    0,
    1,
    0,
    0,
    0.00,
    'Cleared'
),

(
    2,
    1,
    0,
    0,
    0,
    0.00,
    'Pending'
),

(
    3,
    1,
    0,
    0,
    0,
    0.00,
    'Pending'
),

(
    4,
    0,
    0,
    0,
    0,
    0.00,
    'Cleared'
);

-- =========================================================
-- SAMPLE TOURNAMENT
-- =========================================================

INSERT INTO tournaments
(
    tournament_name,
    sport,
    season,
    venue,
    start_date,
    end_date,
    status,
    description,
    created_by
)

VALUES

(
    'Machakos University Inter-Faculty Games',
    'Football',
    '2026/2027',
    'Main Stadium',
    '2026-09-05',
    '2026-09-12',
    'Upcoming',
    'Annual university sports competition.',
    (
        SELECT id FROM users
        WHERE username = 'sports'
    )
);

-- =========================================================
-- TOURNAMENT REGISTRATIONS
-- =========================================================

INSERT INTO tournament_registrations
(
    tournament_id,
    player_id,
    status
)

VALUES

(
    (
        SELECT id
        FROM tournaments
        WHERE tournament_name =
            'Machakos University Inter-Faculty Games'
        LIMIT 1
    ),
    1,
    'Registered'
),

(
    (
        SELECT id
        FROM tournaments
        WHERE tournament_name =
            'Machakos University Inter-Faculty Games'
        LIMIT 1
    ),
    3,
    'Registered'
),

(
    (
        SELECT id
        FROM tournaments
        WHERE tournament_name =
            'Machakos University Inter-Faculty Games'
        LIMIT 1
    ),
    5,
    'Registered'
);

-- =========================================================
-- SAMPLE FIXTURES
-- =========================================================

INSERT INTO tournament_fixtures
(
    tournament_id,
    home_team,
    away_team,
    fixture_date,
    venue,
    referee,
    status
)

VALUES

(
    (
        SELECT id
        FROM tournaments
        WHERE tournament_name =
            'Machakos University Inter-Faculty Games'
        LIMIT 1
    ),
    'Football',
    'Computer Science',
    '2026-09-06 14:00:00',
    'Main Stadium',
    'Referee A',
    'Scheduled'
);

-- =========================================================
-- MODULE 10 COMPLETE
-- =========================================================