# Highlight DQ Rules - External Module Documentation

## Purpose

The **Highlight DQ Rules** external module displays Data Quality (DQ) rule violations directly on data entry forms in REDCap. This allows data managers and authorized users to identify data issues immediately while reviewing records, without needing to navigate to the Data Quality page.

## Compatibility

| Requirement | Version |
|-------------|---------|
| PHP | 8.0.27 - 8.2.29 |
| REDCap | 13.8.1 - 15.9.1 |
| Framework | Version 14 |

## Features

- **Real-time DQ Error Display**: Shows a table of failing DQ rules on the data entry form
- **Role-based Access**: Restricts visibility to specified user roles
- **Inline Field Highlighting**: Optionally highlights fields referenced in failing rules with a red border
- **Exclusion Tracking**: Displays verified/excluded rules in a separate table
- **Direct Links**: Rule names link directly to the Data Quality page for quick access
- **Single-field Verification Handling**: Correctly detects verification status for single-field rules

## Technical Overview

### Module Architecture

| File | Purpose |
|------|---------|
| `HighlightDQRulesModule.php` | Main module class - handles DQ rule detection, role validation, and HTML rendering |
| `config.json` | Module configuration including settings, metadata, and compatibility |
| `js/highlight.js` | Client-side JavaScript for inline field highlighting and icon management |
| `css/styles.css` | Styling for error and exclusion tables and inline error banners |

### Directory Structure

```
highlight_dq_rules_v1.0.0/
├── config.json
├── HighlightDQRulesModule.php
├── DOCUMENTATION.md
├── css/
│   └── styles.css
├── js/
│   └── highlight.js
└── automated_tests/
    ├── fixtures/
    │   ├── cdisc_files/
    │   └── import_files/
    ├── step_definitions/
    │   └── noncore.js
    ├── urs/
    │   └── User Requirement Specification.spec
    └── *.feature (test scenarios)
```

### Hook Used

- **`redcap_data_entry_form`**: Injects DQ error tables and highlighting on data entry forms

### Database Tables Accessed

| Table | Purpose |
|-------|---------|
| `redcap_data_quality_rules` | Retrieves rule details (rule_id, rule_order, rule_name, rule_logic, real_time_execute) |
| `redcap_user_roles` | Validates user role permissions |
| `redcap_user_rights` | Maps users to roles |

### Key Methods

| Method | Visibility | Description |
|--------|-----------|-------------|
| `validateSettings($settings)` | public | Validates that required role configuration is not empty |
| `makeDQLink($projectId, $rule, $val)` | private | Generates an HTML anchor link to the Data Quality rule page |
| `getDQRulesDetails($projId, $ruleIds)` | private | Retrieves rule details from the database for given rule IDs |
| `getRoleNameFromIds($projId, $roleIds, $userName)` | private | Checks if a user belongs to any of the specified roles |
| `redcap_data_entry_form(...)` | public | Main hook - renders DQ error/exclusion tables on data entry forms |

## Configuration Settings

All settings are restricted to super users only.

### Required Settings

| Setting Key | Type | Description |
|------------|------|-------------|
| `user-roles-can-view` | user-role-list (repeatable) | Roles that can view DQ rule errors. At least one role must be selected. |

### Optional Settings

| Setting Key | Type | Default | Description |
|------------|------|---------|-------------|
| `highlight-dq-inline` | checkbox | unchecked | When enabled, fields referenced in failing rules are highlighted with a red border and display related rule IDs. |
| `dont-show-excluded-table` | checkbox | unchecked | When enabled, hides the table showing excluded/verified rules. |
| `dont-reset-field-data-icon` | checkbox | unchecked | When enabled, preserves REDCap's default data status icons instead of resetting them to grey. |

## User Interface

### Error Table (Red)

When DQ errors exist, a red table appears showing:

| Column | Description |
|--------|-------------|
| Rule ID | Internal database identifier (unique) |
| Rule Order | Position as shown in Data Quality page (Rule # column) |
| Rule Name | Clickable link to the rule definition on the Data Quality page |
| Rule Logic | The branching logic expression that defines the rule (HTML-escaped) |

A footer note explains the distinction between rule order and rule ID.

### Excluded Rules Table (Green)

When rules have been verified/excluded, a green table displays the same columns for those rules (unless disabled via the `dont-show-excluded-table` setting).

### Inline Highlighting

When `highlight-dq-inline` is enabled:
- Fields referenced in failing rules receive a red 2px border
- A red banner appears above the field showing "related rule ids: [id1, id2, ...]"

**Note**: A highlighted field indicates it is referenced within a failing rule - it does not necessarily mean that specific field contains the erroneous value.

### Icon Reset

By default, the module resets data status icons (green ticks and red exclamation marks) to the standard grey balloon icon for fields referenced in DQ rules. This can be disabled via the `dont-reset-field-data-icon` setting.

## Workflow

1. User opens a data entry form
2. Module checks if user belongs to an allowed role (or is a super user)
3. If roles are not configured, an alert is displayed prompting configuration
4. If authorized:
   - Includes external CSS (`css/styles.css`) and JS (`js/highlight.js`) files
   - Calls REDCap's `DataQuality::checkViolationsSingleRecord()` to get current violations
   - Filters out excluded rules
   - Handles single-field rules separately: checks verification status via `getExclusionsSingleRecord()` and moves verified rules to the exclusion list
   - Queues icon updates for all fields referenced in rules
   - Renders error table with linked, HTML-escaped rule names and logic
   - Optionally highlights inline fields (if `highlight-dq-inline` is enabled)
   - Renders excluded rules table (if not disabled via `dont-show-excluded-table`)
   - Resets field status icons to grey (if not disabled via `dont-reset-field-data-icon`)

## Security Features

### SQL Injection Prevention

All database queries use **parameterized queries** via the framework's `$this->query()` method with placeholder parameters (`?`). Rule IDs are cast to integers using `array_map('intval', ...)` as an additional safeguard.

### XSS Protection

All user-controllable output is escaped using `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')` before rendering in HTML, including:
- Rule names in table cells and links
- Rule logic in table cells
- Rule logic passed to JavaScript is encoded via `json_encode()`

### Role Validation

Access is validated against configured roles on every page load. Only users assigned to an allowed role (or super users) can see the DQ error tables.

### Settings Validation

The `validateSettings()` method ensures the required `user-roles-can-view` configuration is not empty, preventing the module from operating without proper role configuration.

### Error Handling

Database methods (`getDQRulesDetails`, `getRoleNameFromIds`) use try-catch blocks to gracefully handle exceptions. Errors are logged with the `Highlight DQ Rules:` prefix for easy identification.

## Client-side JavaScript (`js/highlight.js`)

The JavaScript functionality is encapsulated in a module pattern with the following public methods:

| Function | Description |
|----------|-------------|
| `matchField(ruleId, dqLogic)` | Parses rule logic to find field references and maps them to rule IDs |
| `addSingleFieldIconUpdate(fieldName)` | Queues a field for icon reset |
| `highlightInlineErrors()` | Applies red borders and rule ID banners to matched fields |
| `resetFieldIcons()` | Replaces green tick and red exclamation icons with the default grey balloon |

## CSS Styles (`css/styles.css`)

Provides styling for:
- Error table (`#form-instance-rule-errors`) - borders, padding, and font-weight for headers
- Exclusion table (`#form-instance-rule-exclusions`) - same table styling
- Inline error banners (`div[err-data-rule-id]`) - red background, white text, rounded corners

## Troubleshooting

### Module Alert: "Please ensure the mandatory fields..."

**Cause**: No user roles have been configured in the module settings.

**Solution**: Configure at least one user role in the `user-roles-can-view` setting via the module's project-level configuration (accessible by super users only).

### Errors Not Displaying for a User

**Possible Causes**:
1. User is not assigned to an allowed role
2. DQ rules are not configured for real-time execution
3. Module is not enabled for the project
4. User is viewing the form via "View as user" (role check requires actual login)

**Solution**: Verify the user's role assignment matches the configured roles, ensure DQ rules have "Execute in real time" enabled, and confirm the module is enabled for the project. Note that testing role-based access requires logging in as the actual user rather than using "View as user".

### Single-field Rules Not Showing as Excluded

**Cause**: The `checkViolationsSingleRecord()` function returns different results for single-field rules compared to multi-field rules. The module handles this by running `getExclusionsSingleRecord()` to check the verification status of single-field rules.

**Solution**: This is handled automatically. If you observe incorrect behaviour, check that the field has been properly verified in the Data Quality page.

### Error Logging

The module logs errors with the prefix `Highlight DQ Rules:` to the PHP error log. Check your server's error log for detailed error messages when troubleshooting database-related issues.

## Automated Tests

The module includes automated Cypress feature tests in the `automated_tests/` directory covering:

| Test ID | Description |
|---------|-------------|
| E.124.100 | Highlight DQ Rules Configurations |
| E.124.200 | Enable module on all projects |
| E.124.400 | Allow non-admins to enable this module |
| E.124.500 | Hide this module from non-admins |
| E.124.1200 | Exclude field in project |
| E.124.1400 | Module configuration permissions in projects |

Test fixtures include CDISC XML files and CSV data import files. Custom step definitions for non-core steps are in `automated_tests/step_definitions/noncore.js`.

## Authors

- **Richard Hardy** - University of Cambridge, Cambridge Cancer Trials Centre (rmh54@cam.ac.uk)
- **Mintoo Xavier** - Cambridge University Hospital, Cambridge Cancer Trials Centre (mintoo.xavier1@nhs.net)

## Version

Current Version: **1.0.0** (includes post-release security and code quality patches; see [CHANGELOG.md](CHANGELOG.md))
