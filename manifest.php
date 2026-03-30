<?php
/*
 * manifest.php — Library Management module
 *
 * HOW THIS FILE WORKS:
 * Gibbon never reads this file on normal page loads.
 * It is read ONCE — when you click "Install" in Admin → Module Admin.
 * Gibbon includes this file with PHP, reads all the variables below,
 * then builds INSERT SQL statements automatically and runs them.
 * After installation, Gibbon reads from the database, not from this file.
 */


// ════════════════════════════════════════════════════════════════
// PART 1 — Basic module identity
// These 8 variables map directly to columns in the gibbonModule table.
// ════════════════════════════════════════════════════════════════

$name = 'Custom rapport';
// ↑ CRITICAL. This is your module's unique identifier across the whole system.
//   It is stored in gibbonModule.name.
//   The FOLDER on disk must match this name with spaces replaced by underscores:
//   $name = 'Library Management'  →  folder must be named  Library_Management/
//   If these don't match, Gibbon will not recognize your module.
//   You cannot have two modules with the same $name.

$description = 'Generate and archive student bulletin reports';
// ↑ Short human-readable description shown in Admin → Module Admin.
//   Not shown anywhere in the navbar. Keep it under 100 characters.

$entryURL = 'index.php';
// ↑ The PHP file Gibbon loads when a user clicks your module name in the navbar.
//   This must be a filename inside your module folder.
//   Example: if a user clicks "Library Management" in the sidebar,
//   Gibbon opens:  /modules/Library_Management/index.php

$type = 'Additional';
// ↑ Always 'Additional' for any custom or third-party module.
//   Never change this. The value 'Core' is reserved for built-in Gibbon modules only.

$category = 'Other';
// ↑ Controls which GROUP in Gibbon's top-level navigation your module appears under.
//   Valid values: 'Learn', 'People', 'School', 'Admin', 'Other'
//   Example: $category = 'Learn'  →  your module appears inside the "Learn" menu group.

$version = '1.0.2';
// ↑ Version string in MAJOR.MINOR.PATCH format.
//   Shown in Admin → Module Admin.
//   Does NOT trigger auto-updates — it is just a label.

$author = 'ESCM Business School IT';
// ↑ Your name or your organisation's name. Purely informational.

$url = 'https://escm.mg';
// ↑ A URL to your project page or documentation. Purely informational.


// ════════════════════════════════════════════════════════════════
// PART 2 — Database tables your module needs
// Each entry is a SQL CREATE TABLE statement.
// Gibbon runs these automatically when you click Install.
// On Uninstall, Gibbon drops the tables automatically.
// Use empty string '' if your module needs no database tables.
// ════════════════════════════════════════════════════════════════

$moduleTables[] = "CREATE TABLE IF NOT EXISTS `lib_books` (
  `lib_bookID`   INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  -- ↑ Primary key. Always INT UNSIGNED AUTO_INCREMENT.
  --   Prefix the column name with your table abbreviation (lib_) for clarity.

  `title`        VARCHAR(255) NOT NULL,
  `author`       VARCHAR(255) NOT NULL,
  `isbn`         VARCHAR(20)  DEFAULT NULL,
  `quantity`     SMALLINT UNSIGNED NOT NULL DEFAULT 1,

  `status`       ENUM('available','borrowed','lost') NOT NULL DEFAULT 'available',
  -- ↑ Use ENUM for fields with a fixed set of allowed values.
  --   This prevents invalid data at the database level.

  `gibbonPersonIDCreated` INT(10) UNSIGNED DEFAULT NULL,
  -- ↑ Best practice: record WHO created this row.
  --   References gibbonPerson.gibbonPersonID from Gibbon's core tables.

  `timestampCreated`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `timestampModified` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  -- ↑ Best practice: always include audit timestamps.

  PRIMARY KEY (`lib_bookID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
// ↑ Always use utf8mb4 (not utf8) to support all Unicode characters including emoji.

$moduleTables[] = "CREATE TABLE IF NOT EXISTS `lib_loans` (
  `lib_loanID`       INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `lib_bookID`       INT(10) UNSIGNED NOT NULL,
  `gibbonPersonID`   INT(10) UNSIGNED ZEROFILL NOT NULL,
  -- ↑ When referencing gibbonPerson, copy its exact type: INT(10) UNSIGNED ZEROFILL.
  --   If your type doesn't match, MySQL will refuse the foreign key constraint.
  `dateBorrowed`     DATE NOT NULL,
  `dateReturned`     DATE DEFAULT NULL,
  PRIMARY KEY (`lib_loanID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

// If you need no tables at all:
// $moduleTables[] = '';


// ════════════════════════════════════════════════════════════════
// PART 3 — Action rows
//
// THIS IS THE MOST IMPORTANT PART FOR THE NAVBAR.
//
// Each $actionRows[] entry = one row inserted into gibbonAction.
// Each row represents one "page" or "feature" in your module.
// The menuShow field controls whether it appears in the navbar.
//
// After installation, when Gibbon builds the left sidebar it runs:
//   SELECT * FROM gibbonAction
//   WHERE gibbonModuleID = [your module] AND menuShow = 'Y'
//   ORDER BY category, precedence
// Each result row becomes one link in the sidebar.
// ════════════════════════════════════════════════════════════════

// ── Action 0: View Books ────────────────────────────────────────
$actionRows[0]['name'] = 'Générer le bulletin';
// ↑ The text that appears as the clickable link in the left sidebar.
//   Also shown in Admin → User Admin → Manage Roles when assigning permissions.

$actionRows[0]['precedence'] = '0';
// ↑ Controls ordering when two actions have the same name prefix.
//   For most modules just use '0' for everything.
//   Higher number = shown first in grouped actions.

$actionRows[0]['category'] = 'Géneration de bulletin';
// ↑ Creates a sub-heading in the sidebar.
//   All actions with the same category appear grouped under that heading.
//   Example sidebar result:
//     LIBRARY MANAGEMENT         ← module name (from gibbonModule.name)
//       Library                  ← sub-heading (from this category field)
//         View Books             ← this action (because menuShow = 'Y')
//         Manage Books           ← another action in same category

$actionRows[0]['description'] = "Générer un bulletin à partir d'un étudiant et d'un dossier d'archive.";
// ↑ Shown in the permissions manager. Not visible in the navbar.

$actionRows[0]['URLList'] = 'index.php,studentsByYearGroup.php,studentInfo.php,bulletin_generateProcess.php';
// ↑ CRITICAL for security. A comma-separated list of ALL PHP files
//   that belong to this action.
//   Gibbon's isActionAccessible() function checks whether the
//   currently requested URL is in this list.
//   If a PHP file is NOT listed here, isActionAccessible() will
//   DENY access to it — even if the user has the right role.
//
//   Multiple files example:
//   $actionRows[1]['URLList'] = 'addBook.php,editBook.php,deleteBook.php'; 
//   This means addBook, editBook, and deleteBook all share the same
//   permission — a user either has access to all three or none.

$actionRows[0]['entryURL'] = 'index.php';
// ↑ The default/main PHP file for this action.
//   Must be one of the files listed in URLList above.
//   This is the file Gibbon navigates to when the navbar link is clicked.

$actionRows[0]['menuShow'] = 'Y';
// ↑ THE KEY FIELD THAT CONTROLS THE NAVBAR.
//
//   'Y' → this action appears as a link in the left sidebar.
//          The link text is the 'name' field above.
//          The link URL opens the 'entryURL' file.
//
//   'N' → this action is HIDDEN from the sidebar.
//          The permission system still works and protects the URL,
//          but no link appears. Users can only reach this page
//          via a button/link on another page.
//
//   Use 'N' for:
//   - Delete handlers (deleteBook.php)
//   - Form processing pages with no own UI
//   - Pages only reachable from another page's button

// ── Default permissions ─────────────────────────────────────────
$actionRows[0]['defaultPermissionAdmin']   = 'Y';
$actionRows[0]['defaultPermissionTeacher'] = 'Y';
$actionRows[0]['defaultPermissionStudent'] = 'N';
$actionRows[0]['defaultPermissionParent']  = 'N';
$actionRows[0]['defaultPermissionSupport'] = 'N';
// ↑ These set the INITIAL permission state when the module is first installed.
//   They are written to the gibbonPermission table during installation.
//   After installation, an admin can change these via Manage Roles
//   and the defaults here no longer matter — the database value wins.
//
//   'Y' = this role can access this action by default after install.
//   'N' = this role cannot access this action until an admin grants it.

// ── Category permissions ────────────────────────────────────────
$actionRows[0]['categoryPermissionStaff']   = 'Y';
$actionRows[0]['categoryPermissionStudent'] = 'N';
$actionRows[0]['categoryPermissionParent']  = 'N';
$actionRows[0]['categoryPermissionOther']   = 'N';
// ↑ These control whether a role CATEGORY is even ALLOWED to be
//   assigned this permission in the Manage Roles interface.
//
//   'N' = the checkbox for this category won't appear in Manage Roles.
//         Even an admin cannot grant this permission to that category.
//
//   Example: if categoryPermissionParent = 'N', the Parent category
//   will never see or be able to access this action — not even
//   if an admin wants to grant it. It is a hard cap.
//
//   Use 'N' for admin-only actions that should never be accessible
//   to parents or students regardless of school policy.


// ── Action 1: Manage Books (staff only, visible in menu) ────────
$actionRows[1]['name']                      = 'Créer une archive bulletin';
$actionRows[1]['precedence']                = '0';
$actionRows[1]['category']                  = 'Géneration de bulletin';
$actionRows[1]['description']               = 'Créer un dossier pour stocker les bulletins d\'un semestre.';
$actionRows[1]['URLList']                   = 'create_archive.php,create_archiveProcess.php';
// ↑ Three files share one action/permission. A user who has
//   "Manage Books" permission can access all three files.
$actionRows[1]['entryURL']                  = 'create_archive.php';
$actionRows[1]['menuShow']                  = 'Y';
$actionRows[1]['defaultPermissionAdmin']    = 'Y';
$actionRows[1]['defaultPermissionTeacher']  = 'Y';
$actionRows[1]['defaultPermissionStudent']  = 'N';
$actionRows[1]['defaultPermissionParent']   = 'N';
$actionRows[1]['defaultPermissionSupport']  = 'N';
$actionRows[1]['categoryPermissionStaff']   = 'Y';
$actionRows[1]['categoryPermissionStudent'] = 'N';
$actionRows[1]['categoryPermissionParent']  = 'N';
$actionRows[1]['categoryPermissionOther']   = 'N';


// ── Action 2: Delete handler (hidden from menu) ─────────────────
// This is a processing-only page. No need to show it in the navbar.
// It is only ever reached by clicking a Delete button on viewBooks.php.
$actionRows[2]['name']                      = 'Accéder aux bulletins';
$actionRows[2]['precedence']                = '0';
$actionRows[2]['category']                  = 'Géneration de bulletin';
$actionRows[2]['description']               = 'Accéder et ouvrir les bulletins générés par dossier.';
$actionRows[2]['URLList']                   = 'access_archive.php';
$actionRows[2]['entryURL']                  = 'access_archive.php';
$actionRows[2]['menuShow']                  = 'Y';
// ↑ 'N' — this page is NOT in the sidebar. No link for it.
$actionRows[2]['defaultPermissionAdmin']    = 'Y';
$actionRows[2]['defaultPermissionTeacher']  = 'Y';
$actionRows[2]['defaultPermissionStudent']  = 'N';
$actionRows[2]['defaultPermissionParent']   = 'N';
$actionRows[2]['defaultPermissionSupport']  = 'N';
$actionRows[2]['categoryPermissionStaff']   = 'Y';
$actionRows[2]['categoryPermissionStudent'] = 'N';
$actionRows[2]['categoryPermissionParent']  = 'N';
$actionRows[2]['categoryPermissionOther']   = 'N';

// !! COMMON BUG !!
// Never define the same index twice. PHP silently overwrites it:
//   $actionRows[2]['name'] = 'Manage Login Codes'; ← this gets installed
//   $actionRows[2]['name'] = 'Export Bookings';    ← this OVERWRITES index 2
// "Manage Login Codes" disappears completely. Always use a new index.


// ════════════════════════════════════════════════════════════════
// PART 4 — Gibbon Settings (optional)
// Inserts default configuration values into the gibbonSetting table.
// Read later in PHP with: getSettingByScope($connection2, 'Library Management', 'maxLoanDays')
// Use empty string '' if you have no settings.
// ════════════════════════════════════════════════════════════════

$gibbonSetting[] = "INSERT INTO gibbonSetting (scope, name, nameDisplay, description, value)
  VALUES (
    'Library Management',
    -- ↑ scope must exactly match $name above

    'maxLoanDays',
    -- ↑ machine-readable key used in getSettingByScope()

    'Maximum Loan Days',
    -- ↑ human-readable label shown in Admin settings page

    'How many days a book can be borrowed before it is overdue.',
    -- ↑ description shown below the field in settings

    '14'
    -- ↑ default value installed. Admin can change it later.
  );";

// If you have no settings:
// $gibbonSetting[] = '';


// ════════════════════════════════════════════════════════════════
// PART 5 — Hooks (optional, advanced)
// Hooks let your module inject a panel into core Gibbon pages
// like the Student Dashboard or Parental Dashboard.
// Use empty string '' if you don't need hooks.
// ════════════════════════════════════════════════════════════════

$hooks[] = '';
// ↑ No hooks for this module. Leave as '' if unused.
//
// Full hook example (for reference):
// $array = [];
// $array['sourceModuleName']    = 'Library Management';
// $array['sourceModuleAction']  = 'View Books';
// $array['sourceModuleInclude'] = 'hook_studentDashboard_library.php';
// $hooks[] = "INSERT INTO gibbonHook (name, type, options, gibbonModuleID)
//   VALUES ('Library Management', 'Student Dashboard',
//           '" . serialize($array) . "',
//           (SELECT gibbonModuleID FROM gibbonModule WHERE name='Library Management'));";