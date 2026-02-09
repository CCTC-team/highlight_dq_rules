# Highlight DQ Rules

A REDCap External Module that highlights Data Quality rule errors on data entry forms for selected user roles.

## Overview

This module displays Data Quality rule violations directly on data entry screens, making it easier for data managers and other authorized users to identify and address data issues without navigating to the Data Quality page.

Only rules configured to execute in real-time are shown.

## Installation

1. Enable the module in Control Center > External Modules
2. Enable the module for specific projects as needed

## Configuration

### Project Settings

| Setting | Description |
|---------|-------------|
| `user-roles-can-view` | Select one or more user roles that can view DQ rule errors. If no roles are selected, the module is disabled. |
| `highlight-dq-inline` | When checked, highlights fields directly on the form that are referenced in failing DQ rules. |
| `dont-show-excluded-table` | When checked, hides the table showing excluded/verified rules. |
| `dont-reset-field-data-icon` | When checked, preserves the default REDCap data status icons instead of resetting them. |

All settings are restricted to super users only.

## Usage

### Error Table

When a user with an authorized role views a data entry form, a table appears showing:
- **Rule ID** - Internal database identifier for the rule
- **Rule Order** - The Rule # as shown on the Data Quality page
- **Rule Name** - Clickable link to the rule on the Data Quality page
- **Rule Logic** - The logic expression for the rule

### Inline Highlighting

When `highlight-dq-inline` is enabled:
- Fields referenced in failing rules are highlighted with a red border
- A red banner shows the related rule IDs above the highlighted field

Note: A highlighted field indicates it is "referenced within a failing data quality rule" - not necessarily that the field itself contains the error.

### Excluded Rules Table

A separate green table shows rules that have been excluded (verified) for the current record, unless disabled via settings.

## Troubleshooting

### Module not showing errors
- Verify the user is assigned to a role listed in `user-roles-can-view`
- Ensure the DQ rules are configured for real-time execution
- Check that the module is enabled for the project

### Errors in PHP log
The module logs errors to the PHP error log with the prefix "Highlight DQ Rules:". Check your server's error log for details.

#### Automation Testing

This module is tested using automated tests implemented with the **Cypress** framework. To set up Cypress, refer to the following repository:  
https://github.com/vanderbilt-redcap/redcap_cypress

We use a custom Docker instance, **CCTC_REDCap_Docker**, instead of `redcap_docker`. This instance mirrors our Live environment by using the same versions of **MariaDB** and **PHP**.

All automated test scripts are located in the `automated_tests` directory. These scripts can also be used to manually test the external module. The directory contains:
- Custom step definitions created by our team
- Fixture files
- User Requirement Specification (URS) documents
- Feature test scripts