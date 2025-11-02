-- Database Migration Script for Vivarium Manager Role
-- This script adds support for the 'vivarium_manager' role in the MyVivarium system
-- Created: 2025-11-02

-- Step 1: Update any existing users with position "Vivarium Manager" to have the vivarium_manager role
-- This ensures users who already have the position get the appropriate role
UPDATE users
SET role = 'vivarium_manager'
WHERE position = 'Vivarium Manager'
  AND role = 'user'
  AND status = 'approved';

-- Step 2: Verify the update
-- Run this query to see users with vivarium_manager role:
-- SELECT id, name, username, position, role, status FROM users WHERE role = 'vivarium_manager';

-- Step 3: Grant vivarium_manager role to specific user (OPTIONAL - uncomment and modify as needed)
-- Replace 'user@example.com' with the actual username/email
-- UPDATE users SET role = 'vivarium_manager' WHERE username = 'user@example.com' AND status = 'approved';

-- Note: The 'vivarium_manager' role grants access to:
-- 1. View all maintenance notes across all cages
-- 2. Add new maintenance notes to any cage
-- 3. Edit existing maintenance notes
-- 4. Delete maintenance notes
-- 5. Search and filter maintenance records
-- 6. Print maintenance reports
--
-- This role is intended for vivarium facility managers who need oversight
-- of all cage maintenance activities.

-- Verification Queries:
-- Check total users by role:
-- SELECT role, COUNT(*) as count FROM users GROUP BY role;

-- Check vivarium managers specifically:
-- SELECT id, name, username, position, role, status
-- FROM users
-- WHERE role = 'vivarium_manager';
