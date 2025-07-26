Feature: Evaluation Workflow State Management and Authorization
  As a system user
  I want evaluation workflow states to be properly managed across different user roles
  So that authorization is consistent and secure across all accounts

  Background:
    Given the system has the following users:
      | role     | username | employee_id |
      | hr_admin | admin    | 1           |
      | manager  | manager1 | 2           |
      | employee | emp1     | 3           |
    And employee "emp1" reports to manager "manager1"
    And an evaluation exists for employee "emp1" with manager "manager1"

  Scenario: Manager can only edit draft evaluations
    Given I am logged in as "manager1"
    And the evaluation status is "draft"
    When I view the evaluation list
    Then I should see an "Edit" button for the evaluation
    When I click the "Edit" button
    Then I should be able to access the edit form
    And I should be able to make changes

  Scenario: Manager cannot edit submitted evaluations
    Given I am logged in as "manager1"
    And the evaluation status is "submitted"
    When I view the evaluation list
    Then I should NOT see an "Edit" button for the evaluation
    When I try to access the edit URL directly
    Then I should be redirected or see an access denied message

  Scenario: HR Admin can review submitted evaluations
    Given I am logged in as "admin"
    And the evaluation status is "submitted"
    When I view the evaluation list
    Then I should see a "Review" button for the evaluation
    When I click the "Review" button
    Then I should be able to access the edit form
    And I should be able to make changes

  Scenario: Workflow state consistency across user accounts
    Given I am logged in as "manager1"
    And the evaluation status is "draft"
    When I submit the evaluation
    Then the evaluation status should change to "submitted"
    When I log out and log in as "admin"
    And I view the same evaluation
    Then the evaluation status should still be "submitted"
    And I should see a "Review" button instead of "Edit"

  Scenario: State transitions are properly enforced
    Given I am logged in as "manager1"
    And the evaluation status is "draft"
    When I submit the evaluation
    Then the evaluation status should change to "submitted"
    When I try to edit the evaluation again
    Then I should NOT see an "Edit" button
    And direct access to edit URL should be denied

  Scenario: HR Admin workflow management
    Given I am logged in as "admin"
    And the evaluation status is "submitted"
    When I review and approve the evaluation
    Then the evaluation status should change to "approved"
    When I view the evaluation again
    Then I should still be able to access it for review
    But the workflow should reflect the approved state

  Scenario: Employee cannot edit any evaluations
    Given I am logged in as "emp1"
    And the evaluation status is "draft"
    When I view my evaluations
    Then I should NOT see any "Edit" buttons
    When I try to access the edit URL directly
    Then I should be redirected or see an access denied message

  Scenario: Authorization consistency across all pages
    Given I am logged in as "manager1"
    And the evaluation status is "submitted"
    When I check the evaluation list page
    Then I should NOT see an "Edit" button
    When I check the employee view page
    Then I should NOT see an "Edit" button
    When I check the evaluation view page
    Then I should NOT see an "Edit" button
    And all pages should show consistent authorization state

  Scenario: Manager can only see their direct reports' evaluations
    Given I am logged in as "manager1"
    And there are evaluations for employees not under my management
    When I view the evaluation list
    Then I should only see evaluations for my direct reports
    And I should NOT see evaluations for other employees

  Scenario: Database state consistency
    Given I am logged in as "manager1"
    And the evaluation status is "draft"
    When I submit the evaluation
    Then the database should show status as "submitted"
    When I log in as "admin" in a different session
    And I query the same evaluation
    Then the database should still show status as "submitted"
    And the authorization should reflect this state