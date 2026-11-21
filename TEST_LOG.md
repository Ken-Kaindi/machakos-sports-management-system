# Test Log - Machakos Sports Management System
Tester: Ken Kaindi | Date: 21 Nov 2026 | Env: XAMPP PHP 8.1, Chrome, Android

| # | Test Case | Expected | Result |
|---|-----------|----------|--------|
| 1 | Admin login correct | Dashboard opens | PASS |
| 2 | Admin login wrong password | Error message | PASS |
| 3 | Add player | Player appears in list | PASS |
| 4 | Search player by name | Only matching | PASS after fix #2 |
| 5 | Search with `' OR '1'='1` | No injection | PASS after fix #2 |
| 6 | Add fixture | Fixture saved | PASS |
| 7 | Add equipment | Equipment saved | PASS |
| 8 | Clearance request | Status updates | PASS |
| 9 | Edit team | Updated | PASS |
| 10 | Delete player | Removed | PASS |
| 11 | Session after 6 min idle | Still logged in | PASS after fix #1 |
| 12 | 500 records load time | <1.5s | PASS 1.1s after fix #3 |
| 13 | Mobile table view | No overflow | PASS after fix #4 |
| 14 | Logout | Session cleared | PASS |
| 15 | Password reset | Email sent | FAIL - Open Issue #5 |
