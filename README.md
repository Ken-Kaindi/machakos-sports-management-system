# Machakos Sports Management System

A PHP + MySQL web app for managing sports teams, players, fixtures and admin workflows in Machakos County.

Built as a portfolio project to demonstrate full-stack CRUD, authentication, and database design.

## Features
- Admin login + session management
- Player & team registration, edit, delete
- Fixture scheduling and results entry
- Search, filter, pagination for 500+ records
- Responsive admin dashboard (mobile + desktop)

## Tech Stack
- **Backend:** PHP 8, MySQL
- **Frontend:** HTML5, CSS3, JavaScript
- **Database:** MySQL - structured tables for users, teams, players, fixtures
- **Tools:** Git, 【entity-GitHub¦canonical_name=GitHub】, VS Code, XAMPP

## Setup
1. Clone: `git clone https://github.com/Ken-Kaindi/machakos-sports-management-system.git`
2. Import `database/machakos_sports.sql` into MySQL
3. Update `config/db.php` with your local DB credentials
4. Run on XAMPP / localhost: `http://localhost/machakos-sports/`
5. Default admin: `admin@test.com / admin123` (change in production)

## Database Schema
- `users` (id, name, email, password_hash, role)
- `teams` (id, name, coach, created_at)
- `players` (id, team_id, name, position, age)
- `fixtures` (id, home_team, away_team, date, venue, result)

## Testing
See `TEST_LOG.md` for manual test results.
- 15 manual test cases run
- 13 passed, 2 fixed (see Issues #1, #3)
- Tested on Chrome + Android, XAMPP PHP 8.1

## What I Fixed
- Fixed session timeout bug - sessions now persist 2 hours
- Fixed SQL injection in search - moved to prepared statements
- Improved admin page load from 4.2s → 1.1s with pagination + indexing

## Future Improvements
- Password reset via email
- Export fixtures to PDF
- Role-based permissions for coaches

Author: David Kaindi Mbalya / Ken Kaindi - Machakos, Kenya
MSc IT (JKUAT) | BSc IT (Mount Kenya University)
