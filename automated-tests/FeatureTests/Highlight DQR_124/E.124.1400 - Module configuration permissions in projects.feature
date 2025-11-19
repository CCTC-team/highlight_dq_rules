Feature: E.124.1400 - The system shall support the ability to configure 'Module configuration permissions in projects' for Highlight DQ Rules external module.

  As a REDCap end user
  I want to see that Highlight DQ Rules is functioning as expected

Scenario: E.124.1400 - Module configuration permissions in projects
    Given I login to REDCap with the user "Test_Admin"
    When I click on the link labeled "Control Center"
    When I click on the link labeled exactly "Manage"
    Then I should see "External Modules - Module Manager"
    And I should NOT see "Highlight DQ Rules - v1.0.0"
    When I click on the button labeled "Enable a module"
    And I click on the button labeled Enable for the external module named "Highlight DQ Rules"
    And I click on the button labeled "Enable" in the dialog box
    Then I should see "Highlight DQ Rules - v1.0.0"
    
    When I click on the button labeled exactly "Configure"
    Then I should see the dropdown field labeled "Module configuration permissions in projects" with the option "Require Project Setup/Design privilege" selected
    And I click on the button labeled "Save"
    Then I should see "Highlight DQ Rules - v1.0.0"

    When I create a new project named "E.124.1400" by clicking on "New Project" in the menu bar, selecting "Practice / Just for fun" from the dropdown, choosing file "redcap_val/Project_redcap_val_nodata.xml", and clicking the "Create Project" button

    # Enable external module
    And I click on the link labeled exactly "Manage"
    Then I should see "External Modules - Project Module Manager"
    When I click on the button labeled "Enable a module"
    And I click on the button labeled Enable for the external module named "Highlight DQ Rules - v1.0.0"
    Then I should see "Highlight DQ Rules - v1.0.0"

    #VERIFY
    When I click on the link labeled "User Rights"
    And I enter "Test_User1" into the input field labeled "Add with custom rights"
    And I click on the button labeled "Add with custom rights"
    When I check the User Right named "Project Setup & Design"
    Then I should see a checkbox labeled "Highlight DQ Rules" that is checked
    And I click on the button labeled "Add user"
    Then I should see "successfully added"

    # Enable - Require module-specific user privilege
    When I click on the link labeled "Control Center"
    And I click on the link labeled exactly "Manage"
    Then I should see "External Modules - Module Manager"
    And I should see "Highlight DQ Rules - v1.0.0"
    When I click on the button labeled exactly "Configure"
    And I select "Require module-specific user privilege" on the dropdown field labeled "Module configuration permissions in projects"
    And I click on the button labeled "Save"
    Then I should see "Highlight DQ Rules - v1.0.0"

    When I click on the link labeled "My Projects"
    And I click on the link labeled "E.124.1400"

    #VERIFY
    When I click on the link labeled "User Rights"
    And I enter "Test_User2" into the input field labeled "Add with custom rights"
    And I click on the button labeled "Add with custom rights"
    When I check the User Right named "Project Setup & Design"
    Then I should see a checkbox labeled "Highlight DQ Rules" that is unchecked
    And I check the checkbox labeled "Highlight DQ Rules"
    And I click on the button labeled "Add user"
    Then I should see "successfully added"

Scenario: E.124.600 - View Usage of the external module
    When I click on the link labeled "Control Center"
    And I click on the link labeled exactly "Manage"
    Then I should see "Highlight DQ Rules - v1.0.0"
    When I click on the button labeled "View Usage"
    And I should see a link labeled "E.124.1400" in the dialog box
    When I click on the link labeled "E.124.1400" in the dialog box
    Then I should see "Project Home"
    And I should see "E.124.1400"

    # Disable external module in Control Center
    When I click on the link labeled "Control Center"
    And I click on the link labeled exactly "Manage"
    And I click on the button labeled exactly "Disable"
    Then I should see "Disable module?" in the dialog box
    When I click on the button labeled "Disable module" in the dialog box
    Then I should NOT see "Highlight DQ Rules - v1.0.0"
    And I logout

    # Verify no exceptions are thrown in the system
    Given I open Email
    Then I should NOT see an email with subject "REDCap External Module Hook Exception - highlight_dq_rules"