# Test Log - Machakos Sports Management System
Tester: Ken Kaindi | Date: 20 Nov 2026 | Env: XAMPP PHP 8.1, Chrome, Android

| # | Test Case | Expected | Result |
|---|-----------|----------|--------|
| 1 | Admin login correct | Dashboard opens | PASS |
| 2 | Admin login wrong password | Error message | PASS |
| 3 | Add player | Player appears in list | PASS |
| 4 | Search player by name | Only matching | PASS after fix #2 |
| 5 | Search with `' OR '1'='1` | No injection | PASS after fix #2 |
| 6 | Add fixture | Fixture saved | PASS |
| 7 | Edit team | Updated | PASS |
| 8 | Delete player | Removed | PASS |
| 9 | Session after 6 min idle | Still logged in | PASS after fix #1 |
| 10 | 500 records load time | <1.5s | PASS 1.1s after fix #3 |
| 11 | Mobile table view | No overflow | PASS after fix #4 |
| 12 | Logout | Session cleared | PASS |
| 13 | SQL import | DB creates clean | PASS |
| 14 | Password reset | Email sent | FAIL - Open Issue #5 |
| 15 | Export PDF | PDF downloads | FAIL - Not built yet |
