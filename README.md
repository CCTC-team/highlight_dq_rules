# Highlight DQ Rules

[![Highlight DQ Rules EM Cypress Tests](https://github.com/CCTC-team/highlight_dq_rules/actions/workflows/cypress-tests.yml/badge.svg)](https://github.com/CCTC-team/highlight_dq_rules/actions/workflows/cypress-tests.yml)

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

### Configuration audit log

Every change to the module's configuration is recorded to the module's **View Logs** page (Control Center →
External Modules → View Logs). One entry is written per changed setting, recording the setting name, its old and
new values, the user who made the change and when. The first save on a freshly configured module logs the values
that were actually set (as `(empty) -> value`); settings left blank are not logged. Old and new values are held as
log parameters, which REDCap shows to super-users via the **Show Parameters** button.

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

The `automated_tests` directory contains the User Requirement Specification (URS) documents, Cypress/Gherkin feature scripts and fixture files for this module. The scripts can also be followed to test the module manually. To run them, see [Setup_Overview.md](https://github.com/CCTC-team/CCTC_REDCap_Docker/blob/redcap_val/Setup_Overview.md).

---

## Who are we

The Cambridge Cancer Trials Centre (CCTC) is a collaboration between Cambridge University Hospitals NHS Foundation Trust, the University of Cambridge, and Cancer Research UK. Founded in 2007, CCTC designs and conducts clinical trials and studies to improve outcomes for patients with cancer or those at risk of developing it. In 2011, CCTC began hosting the Cambridge Clinical Trials Unit - Cancer Theme (CCTU-CT).

CCTC has two divisions: Cancer Theme, which coordinates trial delivery, and Clinical Operations.