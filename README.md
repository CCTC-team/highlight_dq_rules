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

The module includes comprehensive **Cypress automated** tests using the **Cucumber/Gherkin framework**. To set up Cypress, refer to [Setup_Overview.md](https://github.com/CCTC-team/CCTC_REDCap_Docker/blob/redcap_val/Setup_Overview.md).

All automated test scripts are located in the `automated_tests` directory. The test suite automatically picks up the scripts from this folder. These scripts can also be used to manually test the external module. The directory contains:
- Custom step definitions created by our team
- Fixture files
- User Requirement Specification (URS) documents
- Feature test scripts

**Step Definition Locations:**

Step definitions are organized across multiple locations in the `redcap_cypress` repo under `redcap_cypress/cypress/support/step_definitions/`:

- **Non-core feature step definitions** are in `redcap_cypress/cypress/support/step_definitions/noncore.js`
- **Shared EM step definitions** (used by more than one external module) are in `redcap_cypress/cypress/support/step_definitions/external_module.js`
- **EM-specific step definitions** (used only by this module) are in `automated_tests/step_definitions/external_module.js` within this module's repo

#### GitHub Actions Workflow

The module ships with a CI workflow at [.github/workflows/cypress-tests.yml](.github/workflows/cypress-tests.yml) that runs the Cypress suite end-to-end against a freshly built REDCap stack.

**Triggers**
- `push` to `main`
- Manual `workflow_dispatch`

**What it does**
1. Checks out the Highlight DQ Rules EM (this repo) into `highlight_dq_rules_em/`.
2. Clones the `redcap_val` branch of [`CCTC-team/redcap_cypress`](https://github.com/CCTC-team/redcap_cypress) and [`CCTC-team/CCTC_REDCap_Docker`](https://github.com/CCTC-team/CCTC_REDCap_Docker), and the matching REDCap version branch of [`CCTC-team/redcap_source`](https://github.com/CCTC-team/redcap_source).
3. Reads `redcap_version`, `mysql.docker_container`, `mysql.host`, and `mysql.port` from `cypress.env.json.example` so the rest of the job stays in sync with the Cypress config.
4. Injects this EM into `CCTC_REDCap_Docker/redcap_source/modules/highlight_dq_rules_v1.0.1` and brings the Docker stack up (`app`, `db`, `mailhog`).
5. Configures `cypress.env.json`, installs Cypress, and patches an `rctf` after-run handler bug.
6. Builds the spec list from `automated_tests/E.124.*.feature` (excluding `*REDUNDANT*`) and runs them via `npm run test:retry-failed` (up to 3 attempts per spec, Chrome).
7. Merges mochawesome JSON reports and uploads test results, videos, and (on failure) screenshots as artifacts retained for 30 days.

**Required repository secrets**
- `CCTC_TEAM_PAT` — PAT with read access to the CCTC-team repos, including `redcap_source`.
- `PROJECT_ID` — Cypress Cloud project ID substituted into `cypress.config.js`.
- `CYPRESS_RECORD_KEY` — Cypress Cloud record key (recording is gated by `CYPRESS_DISABLE_RECORDING`, currently set to `1`).

**Branch / version pins** (set as `env` at the top of the workflow)
- `CCTC_DOCKER_BRANCH`, `CYPRESS_BRANCH` — both default to `redcap_val`.
- `EM_NAME` / `EM_VERSION` — `highlight_dq_rules` / `v1.0.1`. Bump `EM_VERSION` when releasing a new module version so the spec glob and inject path stay aligned.

---

## Who are we

The Cambridge Cancer Trials Centre (CCTC) is a collaboration between Cambridge University Hospitals NHS Foundation Trust, the University of Cambridge, and Cancer Research UK. Founded in 2007, CCTC designs and conducts clinical trials and studies to improve outcomes for patients with cancer or those at risk of developing it. In 2011, CCTC began hosting the Cambridge Clinical Trials Unit - Cancer Theme (CCTU-CT).

CCTC has two divisions: Cancer Theme, which coordinates trial delivery, and Clinical Operations.