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

The module ships with a CI workflow at [.github/workflows/cypress-tests.yml](.github/workflows/cypress-tests.yml) that runs this module's own Cypress specs end-to-end against a prebuilt all-in-one REDCap image, using a self-contained Cypress runner image. There is no 3-container compose, no host `npm ci`, and no cloning of the harness at runtime — both images are published ahead of time and pulled from GHCR.

**Triggers**
- `push` to `main` (ignoring doc-only changes: `**/*.md`, `LICENSE`, `.gitignore`, `docs/**`)
- Manual `workflow_dispatch`

**What it does** (`cypress-tests` job)
1. Checks out the Highlight DQ Rules EM (this repo) into `highlight_dq_rules_em/`.
2. Logs in to GHCR and pulls two prebuilt images: `redcap-aio` (REDCap + MariaDB + MailHog in one container via supervisord) and `cypress-runner-aio` (the suite with `rctf` + `redcap_rsvc` baked in).
3. Stages the EM under test — strips `.git`/`.github` so only the module payload remains.
4. Starts the AIO container (ports `8443`/`8025`, volume `cctc_mariadb_data`), bind-mounting **this commit's** EM over the image's `modules/highlight_dq_rules_v1.1.1` so REDCap serves the code under test with no rebuild.
5. Waits for REDCap to come up (first boot initialises the DB).
6. Runs the runner image, which copies this module's `automated_tests` out of the container and runs only its `E.124.*` specs (excluding `*REDUNDANT*`), up to 3 attempts per spec, on Chromium. It reaches the DB/files over the mounted Docker socket and the UI over host networking.
7. Uploads the mochawesome reports (and, on failure, screenshots) as artifacts retained for 7 days.

**Follow-on jobs**
- `prune-artifacts` — deletes artifacts from older runs, keeping only the latest 2.
- `publish-report` — merges the run's mochawesome JSON into one combined HTML report and publishes it to GitHub Pages (report named `highlight_dq_rules_v1.1.1.html`, also served at the Pages root as `index.html`).

**Required repository secrets**
- `CCTC_TEAM_PAT` — PAT with `read:packages` for the private `redcap-aio` / `cypress-runner-aio` GHCR images.

**Version pins** (set as `env` at the top of the workflow)
- `AIO_IMAGE` / `RUNNER_IMAGE` — the GHCR image refs; both must be built for the **same** REDCap version.
- `EM_NAME` / `EM_VERSION` — `highlight_dq_rules` / `v1.1.1`. `EM_MODULE` (`highlight_dq_rules_v1.1.1`) is the directory REDCap discovers the module by and the runner uses to locate the specs. Bump `EM_VERSION`/`EM_MODULE` when releasing a new module version so the mount path and spec discovery stay aligned.

---

## Who are we

The Cambridge Cancer Trials Centre (CCTC) is a collaboration between Cambridge University Hospitals NHS Foundation Trust, the University of Cambridge, and Cancer Research UK. Founded in 2007, CCTC designs and conducts clinical trials and studies to improve outcomes for patients with cancer or those at risk of developing it. In 2011, CCTC began hosting the Cambridge Clinical Trials Unit - Cancer Theme (CCTU-CT).

CCTC has two divisions: Cancer Theme, which coordinates trial delivery, and Clinical Operations.