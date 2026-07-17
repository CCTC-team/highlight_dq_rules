Feature: E.124.1400 - The system shall record configuration changes for the Highlight DQ Rules external module (who, when, old->new) to the module's View Logs page.

  As a REDCap administrator
  I want every configuration change to be written to the module's External Module Logs
  So that there is an audit trail of who changed which setting, when, and from what value to what.

  Scenario: Enable external module from Control Center
    Given I login to REDCap with the user "Test_Admin"
    When I click on the link labeled "Control Center"
    And I click on the link labeled "Manage"
    Then I should see "External Modules - Module Manager"
    And I should NOT see "Highlight DQ Rules - v1.1.0"
    When I click on the button labeled "Enable a module"
    And I wait for 2 seconds
    Then I should see "Available Modules"
    And I click on the button labeled "Enable" in the row labeled "Highlight DQ Rules"
    And I wait for 1 second
    And I click on the button labeled "Enable"
    Then I should see "Highlight DQ Rules - v1.1.0"

  Scenario: First configuration save logs the initial values
    Given I create a new project named "E.124.1400" by clicking on "New Project" in the menu bar, selecting "Practice / Just for fun" from the dropdown, choosing file "fixtures/cdisc_files/Project_redcap_val_nodata.xml", and clicking the "Create Project" button
    And I click on the link labeled "Manage"
    Then I should see "External Modules - Project Module Manager"
    When I click on the button labeled "Enable a module"
    And I click on the button labeled "Enable" in the row labeled "Highlight DQ Rules - v1.1.0"
    Then I should see "Highlight DQ Rules - v1.1.0"

    # First save has no prior snapshot, so each setting the admin actually sets is
    # logged as (empty) -> value. Blank settings stay empty and are not logged.
    # user-roles-can-view is required and repeatable, so set TWO roles (DataManager
    # + DataEntry); highlight-dq-inline is the setting whose value is predictable
    # ((empty) -> 1).
    Given I click on the button labeled "Configure"
    Then I should see "Configure Module"
    When I select "DataManager" on the dropdown field labeled "1. A role that can view the highlight DQ rule errors"
    And I click on the button labeled "+"
    And I select "DataEntry" on the dropdown field labeled "2. A role that can view the highlight DQ rule errors"
    And I check the checkbox labeled "When checked, shows the data quality rule error in line with the question"
    Then I click on the button labeled "Save"
    And I should see "Highlight DQ Rules - v1.1.0"

    #VERIFY - the audit trail on the module's own View Logs page
    When I click on the link labeled "View Logs"
    Then I should see "External Module Logs"
    And I should see a table header and row containing the following values in a table:
      | Module             | Message                         | UserName   |
      | highlight_dq_rules | Configuration changed (project) | Test_Admin |

    # The hook logs one entry per changed key in config.json order, so newest-first
    # the FIRST button is highlight-dq-inline ((empty) -> 1) and the SECOND button is
    # user-roles-can-view. old->new live in params 'old_value'/'new_value'; 'setting'
    # names the changed key. The acting user is the UserName column, not a param.
    When I click on the first button labeled "Show Parameters"
    Then I should see "Log Entry Parameters"
    And I should see a table header and row containing the following values in a table:
      | Name      | Value               |
      | setting   | highlight-dq-inline |
      | old_value | (empty)             |
      | new_value | 1                   |
    And I click on the button labeled "Close"
    Then I should see "External Module Logs"

    # The second entry is the repeatable role list ((empty) -> the two selected roles).
    # rctf resets the database before each feature, so E.124.1400's roles get
    # deterministic ids (DataEntry=1, DataManager=2, Monitor=3); the list stores ids in
    # selection order, so DataManager (slot 1) then DataEntry (slot 2) is ["2","1"].
    When I click on the second button labeled "Show Parameters"
    Then I should see "Log Entry Parameters"
    And I should see a table header and row containing the following values in a table:
      | Name      | Value               |
      | setting   | user-roles-can-view |
      | old_value | (empty)             |
      | new_value | ["2","1"]           |

  Scenario: Changing a setting logs an old->new audit entry
    # rctf starts each scenario from a clean browser page, so re-navigate to the
    # project fresh (same pattern as E.124.700's continuation scenarios).
    Given I login to REDCap with the user "Test_Admin"
    When I click on the link labeled "My Projects"
    And I click on the link labeled "E.124.1400"
    And I click on the link labeled "Manage"
    Then I should see "External Modules - Project Module Manager"
    And I should see "Highlight DQ Rules - v1.1.0"

    # Turn the inline-highlight checkbox back off. This is a genuine 1 -> (empty)
    # transition, proving the snapshot/diff works across saves (not just first save)
    # and that un-setting a value is audited too.
    When I click on the button labeled "Configure"
    Then I should see "Configure Module"
    And I uncheck the checkbox labeled "When checked, shows the data quality rule error in line with the question"
    Then I click on the button labeled "Save"
    And I should see "Highlight DQ Rules - v1.1.0"

    #VERIFY - the audit trail on the module's own View Logs page
    When I click on the link labeled "View Logs"
    Then I should see "External Module Logs"
    And I should see a table header and row containing the following values in a table:
      | Module             | Message                         | UserName   |
      | highlight_dq_rules | Configuration changed (project) | Test_Admin |

    # old->new values live in admin-gated parameters. The most recent entry is the
    # highlight-dq-inline 1 -> (empty) change.
    When I click on the first button labeled "Show Parameters"
    Then I should see "Log Entry Parameters"
    And I should see a table header and row containing the following values in a table:
      | Name      | Value               |
      | setting   | highlight-dq-inline |
      | old_value | 1                   |
      | new_value | (empty)             |
    And I click on the button labeled "Close"
    Then I should see "External Module Logs"

    # Disable the external module from the Control Center
    When I click on the link labeled "Control Center"
    And I click on the link labeled "Manage"
    Then I should see "External Modules - Module Manager"
    And I click on the button labeled "Disable"
    Then I should see "Disable module?"
    When I click on the button labeled "Disable module"
    Then I should NOT see "Highlight DQ Rules - v1.1.0"

    # Verify no exceptions are thrown in the system
    Given I open Email
    Then I should NOT see an email with subject "REDCap External Module Hook Exception - highlight_dq_rules"
