Feature: E.124.100 - The system shall support the ability to enable/disable Highlight DQ Rules external module.

  As a REDCap end user
  I want to see that Highlight DQ Rules is functioning as expected

  Scenario: E.124.100 - Enable external module - Default settings
    Given I login to REDCap with the user "Test_Admin"
    When I click on the link labeled "Control Center"
    And I click on the link labeled "Manage"
    Then I should see "External Modules - Module Manager"
    And I should NOT see "Highlight DQ Rules - v1.0.0"
    When I click on the button labeled "Enable a module"
    And I wait for 2 seconds
    Then I should see "Available Modules"
    And I click on the button labeled "Enable" in the row labeled "Highlight DQ Rules"
    And I wait for 1 second
    And I click on the button labeled "Enable"
    Then I should see "Highlight DQ Rules - v1.0.0"
    And I logout
    
    Given I login to REDCap with the user "Test_User1"
    When I create a new project named "E.124.100" by clicking on "New Project" in the menu bar, selecting "Practice / Just for fun" from the dropdown, choosing file "fixtures/cdisc_files/Project_redcap_val_nodata.xml", and clicking the "Create Project" button
    #And I should NOT see a link labeled "Manage"
    And I logout

    # Disable external module in Control Center
    Given I login to REDCap with the user "Test_Admin"
    When I click on the link labeled "Control Center"
    And I click on the link labeled "Manage"
    And I click on the button labeled "Disable"
    Then I should see "Disable module?"
    When I click on the button labeled "Disable module"
    Then I should NOT see "Highlight DQ Rules - v1.0.0"
    And I logout

    # Verify no exceptions are thrown in the system
    Given I open Email
    Then I should NOT see an email with subject "REDCap External Module Hook Exception - highlight_dq_rules"