# Vivarium Manager Feature - Implementation Guide

## Overview
This document describes the Vivarium Manager feature implementation for MyVivarium project.

**Implementation Date:** 2025-11-02
**Version:** 1.0
**Feature Type:** Full-Featured with Complete CRUD Operations

---

## Features Implemented

### 1. New User Role: Vivarium Manager

**Role Name:** `vivarium_manager`

**Permissions:**
- ✅ View all maintenance notes across all cages
- ✅ Add new maintenance notes to any cage
- ✅ Edit existing maintenance notes
- ✅ Delete maintenance notes
- ✅ Search and filter maintenance records
- ✅ Print maintenance reports
- ✅ Access to dedicated Vivarium Management dashboard

**Access Level:**
- Higher than regular users
- Lower than admin (cannot manage users, IACUC, strains, or lab settings)

---

## Files Modified

### 1. **register.php** (Line 366)
**Change:** Updated position dropdown
```php
// Before:
<option value="Lab Manager">Lab Manager</option>

// After:
<option value="Vivarium Manager">Vivarium Manager</option>
```

**Impact:** Users can now select "Vivarium Manager" as their position during registration.

---

### 2. **manage_users.php** (Lines 41-63, 195-204)

**Backend Changes:**
```php
// Added new case in switch statement (Line 55-57):
case 'vivarium_manager':
    $query = "UPDATE users SET role='vivarium_manager' WHERE username=?";
    break;
```

**UI Changes:** Added buttons to set user role to Vivarium Manager
- Users with 'user' role: Can be promoted to Admin or Vivarium Manager
- Users with 'admin' role: Can be demoted to User or Vivarium Manager
- Users with 'vivarium_manager' role: Can be promoted to Admin or demoted to User

**Icon Used:** `<i class="fas fa-flask"></i>` (Flask icon for Vivarium Manager)

---

### 3. **header.php** (Lines 203-210)

**Added Navigation Menu Section:**
```php
// Display Vivarium Manager menu for vivarium_manager and admin roles
if (isset($_SESSION['role']) &&
    ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'vivarium_manager')) {
    echo '<li><hr class="dropdown-divider"></li>';
    echo '<li class="dropdown-header">Vivarium Management</li>';
    echo '<li><a class="dropdown-item" href="vivarium_manager_notes.php">
          <i class="fas fa-clipboard-list"></i> Maintenance Notes</a></li>';
}
```

**Menu Structure:**
```
Settings Dropdown:
├── User Profile
├── Tasks & Reminders
├── ───────────────────────  (if vivarium_manager or admin)
├── Vivarium Management
│   └── Maintenance Notes    (NEW)
├── ───────────────────────  (if admin only)
├── Administration
│   ├── Manage Users
│   ├── Manage IACUC
│   ├── Manage Strain
│   ├── Manage Lab
│   └── Export CSV
├── ───────────────────────
└── Logout
```

---

### 4. **README.md**

**Added Documentation:**
```
- vivarium_manager_notes.php: Comprehensive maintenance notes management interface
  for Vivarium Managers and Admins. Features include viewing all maintenance notes
  across all cages, searching/filtering, adding, editing, and deleting notes,
  plus print functionality.
```

---

## New Files Created

### 1. **vivarium_manager_notes.php** (Main Feature File)

**File Size:** ~800 lines
**Technology Stack:**
- PHP 8.1+ with MySQLi prepared statements
- Bootstrap 5.1.3
- Font Awesome 5.15.3
- Vanilla JavaScript (Fetch API)

**Features:**

#### A. View All Maintenance Notes
- Displays all maintenance records from all cages
- Shows: ID, Cage ID, Date/Time, User Name, Comments
- Pagination: 50 records per page
- Responsive table design with mobile support

#### B. Search & Filter
- Real-time search across multiple fields:
  - Cage ID
  - Comments/Notes
  - User name
  - Username
- Search preserved across pagination
- Clear search button

#### C. Add New Notes (Modal)
- Modal popup form
- Required: Cage ID selection (dropdown)
- Optional: Comments (textarea)
- AJAX submission (no page reload)
- CSRF protection
- Instant feedback messages

#### D. Edit Notes (Modal)
- Click edit button on any note
- Loads existing data via AJAX
- Pre-populates form fields
- AJAX update (no page reload)
- CSRF protection

#### E. Delete Notes
- Click delete button
- Confirmation dialog
- AJAX deletion
- Cannot be undone (user warned)
- CSRF protection

#### F. Print Functionality
- CSS print styles (`@media print`)
- Hides UI elements (buttons, search, pagination)
- Shows print header with:
  - Lab name
  - Report title
  - Generation timestamp
  - Search filter (if active)
- Professional formatted output

#### G. Security Features
- ✅ Role-based access control (admin + vivarium_manager only)
- ✅ CSRF token validation on all POST requests
- ✅ Prepared statements for all SQL queries
- ✅ Input sanitization (FILTER_SANITIZE_STRING)
- ✅ Output escaping (htmlspecialchars)
- ✅ Session validation
- ✅ Proper error handling

---

### 2. **database/add_vivarium_manager_role.sql**

**Purpose:** Database migration script

**Features:**
```sql
-- Step 1: Auto-upgrade users with position "Vivarium Manager"
UPDATE users
SET role = 'vivarium_manager'
WHERE position = 'Vivarium Manager'
  AND role = 'user'
  AND status = 'approved';

-- Step 2: Manual assignment (commented out, optional)
-- UPDATE users SET role = 'vivarium_manager'
-- WHERE username = 'user@example.com' AND status = 'approved';
```

**Verification Queries Included:**
- Check users by role count
- List all vivarium managers

---

## Database Schema

**No schema changes required!** ✅

The existing `maintenance` table structure is perfect:
```sql
CREATE TABLE `maintenance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cage_id` varchar(255) NOT NULL,
  `user_id` int NOT NULL,
  `comments` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`cage_id`) REFERENCES `cages` (`cage_id`) ON UPDATE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);
```

The `users` table already supports the role field (varchar 255).

---

## API Endpoints (AJAX)

All AJAX requests go to `vivarium_manager_notes.php` with `POST` method and `ajax=1` parameter.

### 1. Add Note
```javascript
POST vivarium_manager_notes.php
{
  ajax: 1,
  action: 'add',
  cage_id: 'HC001',
  comments: 'Cage cleaned and sanitized',
  csrf_token: '...'
}

Response:
{
  success: true,
  message: 'Maintenance note added successfully'
}
```

### 2. Get Note (for editing)
```javascript
POST vivarium_manager_notes.php
{
  ajax: 1,
  action: 'get_note',
  note_id: 123,
  csrf_token: '...'
}

Response:
{
  success: true,
  data: {
    id: 123,
    cage_id: 'HC001',
    comments: 'Cage cleaned and sanitized'
  }
}
```

### 3. Edit Note
```javascript
POST vivarium_manager_notes.php
{
  ajax: 1,
  action: 'edit',
  note_id: 123,
  cage_id: 'HC001',
  comments: 'Updated comment text',
  csrf_token: '...'
}

Response:
{
  success: true,
  message: 'Maintenance note updated successfully'
}
```

### 4. Delete Note
```javascript
POST vivarium_manager_notes.php
{
  ajax: 1,
  action: 'delete',
  note_id: 123,
  csrf_token: '...'
}

Response:
{
  success: true,
  message: 'Maintenance note deleted successfully'
}
```

---

## Testing Checklist

### Pre-Deployment
- [ ] Run database migration script
- [ ] Verify no SQL errors
- [ ] Check users table for new role

### User Registration
- [ ] Open register.php
- [ ] Verify "Vivarium Manager" appears in position dropdown
- [ ] Register new user with Vivarium Manager position
- [ ] Confirm registration successful

### User Management (Admin Only)
- [ ] Login as admin
- [ ] Go to Settings > Administration > Manage Users
- [ ] Verify "Vivarium Mgr" button appears with flask icon
- [ ] Click "Vivarium Mgr" button for a test user
- [ ] Confirm user role updated to 'vivarium_manager'
- [ ] Verify conditional buttons appear correctly for each role

### Navigation Menu
- [ ] Login as regular user
- [ ] Confirm "Maintenance Notes" does NOT appear in menu
- [ ] Logout and login as vivarium_manager
- [ ] Confirm "Vivarium Management" section appears
- [ ] Confirm "Maintenance Notes" link visible
- [ ] Login as admin
- [ ] Confirm "Vivarium Management" section appears
- [ ] Confirm "Administration" section also appears

### Vivarium Manager Notes Page
#### Access Control
- [ ] Attempt to access as regular user (should redirect to index.php)
- [ ] Access as vivarium_manager (should load)
- [ ] Access as admin (should load)

#### View Functionality
- [ ] Page loads without errors
- [ ] All existing maintenance notes displayed
- [ ] Table shows: ID, Cage ID, Date/Time, User, Comments, Actions
- [ ] User initials displayed correctly
- [ ] Pagination works (if >50 records)
- [ ] Timestamp formatted correctly

#### Search Functionality
- [ ] Search by cage ID (e.g., "HC001")
- [ ] Search by comment keywords
- [ ] Search by user name
- [ ] Clear search button works
- [ ] Search persists across pagination
- [ ] No results message appears when appropriate

#### Add Note
- [ ] Click "Add Note" button
- [ ] Modal opens
- [ ] Cage dropdown populated with all cages
- [ ] Select cage
- [ ] Enter comments
- [ ] Click "Save Note"
- [ ] Success message appears
- [ ] Page refreshes automatically
- [ ] New note appears in table
- [ ] CSRF validation works (test by modifying token)

#### Edit Note
- [ ] Click edit button on any note
- [ ] Modal opens
- [ ] Existing data pre-filled
- [ ] Modify cage ID
- [ ] Modify comments
- [ ] Click "Update Note"
- [ ] Success message appears
- [ ] Page refreshes
- [ ] Changes reflected in table

#### Delete Note
- [ ] Click delete button
- [ ] Confirmation dialog appears
- [ ] Click "Cancel" - nothing happens
- [ ] Click delete again
- [ ] Click "OK" - note deleted
- [ ] Success message appears
- [ ] Page refreshes
- [ ] Note removed from table

#### Print Functionality
- [ ] Click "Print" button
- [ ] Print preview opens
- [ ] Header includes: Lab name, title, timestamp
- [ ] Search filter shown (if active)
- [ ] Buttons/navigation hidden
- [ ] Table formatted properly
- [ ] All notes visible
- [ ] Print or cancel

### Existing Features (Regression Testing)
- [ ] maintenance.php still works (add notes from cage view)
- [ ] bc_view.php displays correctly
- [ ] hc_view.php displays correctly
- [ ] User registration works
- [ ] Login works for all user types
- [ ] Admin panel works
- [ ] Other CRUD operations work

---

## Deployment Instructions

### Step 1: Backup Database
```bash
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql
```

### Step 2: Run Migration Script
```bash
mysql -u username -p database_name < database/add_vivarium_manager_role.sql
```

Or via phpMyAdmin:
1. Select database
2. Click "SQL" tab
3. Copy contents of `add_vivarium_manager_role.sql`
4. Execute

### Step 3: Upload Files
Upload modified files:
- register.php
- manage_users.php
- header.php
- README.md
- vivarium_manager_notes.php (new)
- database/add_vivarium_manager_role.sql (new)

### Step 4: Verify Deployment
1. Test with different user roles
2. Check error logs for any PHP errors
3. Verify all features working

### Step 5: Assign Vivarium Managers
1. Login as admin
2. Go to Manage Users
3. Click "Vivarium Mgr" button for appropriate users
4. Notify users of new access

---

## Troubleshooting

### Issue: Navigation menu doesn't show "Maintenance Notes"
**Solution:**
- Verify user has role 'vivarium_manager' or 'admin'
- Check session is active
- Clear browser cache

### Issue: "CSRF token validation failed"
**Solution:**
- Refresh the page
- Check session timeout settings
- Verify cookies enabled

### Issue: Ajax requests not working
**Solution:**
- Check browser console for JavaScript errors
- Verify fetch API supported (modern browsers)
- Check network tab for 500 errors

### Issue: Notes not displaying
**Solution:**
- Verify maintenance table has records
- Check user_id foreign key relationships
- Check pagination offset calculation

---

## Security Considerations

### Implemented Protections
1. **Role-Based Access Control:** Only admin and vivarium_manager can access
2. **CSRF Protection:** All forms use CSRF tokens
3. **SQL Injection:** All queries use prepared statements
4. **XSS Protection:** All output uses htmlspecialchars()
5. **Input Validation:** All inputs sanitized
6. **Session Security:** Session validation on every request
7. **Authorization:** Delete operations verify ownership

### Best Practices Applied
- Principle of least privilege
- Defense in depth
- Input validation + output encoding
- Parameterized queries
- Error handling without information disclosure
- Secure session management

---

## Future Enhancements (Optional)

### Version 1.1 Potential Features
- [ ] Export maintenance notes to CSV/PDF
- [ ] Email notifications for new maintenance notes
- [ ] Attach photos to maintenance notes
- [ ] Maintenance schedules and reminders
- [ ] Bulk operations (delete multiple notes)
- [ ] Advanced filters (date range, cage type)
- [ ] Maintenance note templates
- [ ] Analytics/reporting dashboard

---

## Support & Maintenance

### Regular Maintenance Tasks
- Monthly: Review and archive old notes
- Quarterly: Verify database integrity
- Annually: Security audit

### Contact
For issues or questions about this feature:
- Check GitHub issues: myvivarium/MyVivarium
- Review this documentation
- Contact system administrator

---

## Changelog

### Version 1.0 (2025-11-02)
- ✅ Initial implementation
- ✅ Full CRUD operations
- ✅ Search and pagination
- ✅ Print functionality
- ✅ Role-based access control
- ✅ AJAX-powered UI
- ✅ Mobile responsive design
- ✅ Comprehensive security measures

---

## License
This feature is part of the MyVivarium project and follows the same license terms (LGPL-3.0).

---

**End of Documentation**
