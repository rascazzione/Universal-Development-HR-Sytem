Feature: Bug Reproduction - Workflow State Sharing Issue
  As a system administrator
  I want to reproduce and verify the fix for the workflow state sharing bug
  So that submitted evaluations maintain their state across different user accounts

  Background:
    Given the system is reset with fresh data
    And I have the following test users:
      | role     | username | password | employee_id |
      | hr_admin | admin    | admin123 | 1           |
      | manager  | manager1 | mgr123   | 2           |
      | employee | emp1     | emp123   | 3           |
    And employee "emp1" (ID: 3) reports to manager "manager1" (ID: 2)

  Scenario: Reproduce the original bug - Workflow state not shared
    # Step 1: Create evaluation as HR Admin
    Given I am logged in as "admin" (HR Admin)
    When I create a new evaluation for employee "emp1"
    Then the evaluation should be created with status "draft"
    And the evaluation should have manager_id = 2

    # Step 2: Manager submits the evaluation
    Given I log out and log in as "manager1" (Manager)
    When I view my team evaluations
    Then I should see the evaluation for "emp1" with status "draft"
    And I should see an "Edit" button for this evaluation
    When I click "Edit" and submit the evaluation
    Then the evaluation status should change to "submitted"
    And I should NO LONGER see an "Edit" button

    # Step 3: Verify HR Admin sees the submitted state (this was the bug)
    Given I log out and log in as "admin" (HR Admin)
    When I view the evaluation list
    Then I should see the evaluation with status "submitted"
    And I should see a "Review" button (not "Edit")
    And I should NOT be able to "resubmit" the evaluation
    When I click "Review"
    Then I should access the evaluation in review mode
    And the form should reflect "submitted" state

  Scenario: Verify authorization consistency across all pages
    Given an evaluation exists with status "submitted"
    And I am logged in as "manager1" (Manager)
    
    # Check evaluation list page
    When I visit "/evaluation/list.php"
    Then I should NOT see an "Edit" button for submitted evaluations
    
    # Check employee view page
    When I visit "/employees/view.php?id=3"
    Then I should NOT see an "Edit" button for submitted evaluations
    
    # Check evaluation view page
    When I visit "/evaluation/view.php?id={evaluation_id}"
    Then I should NOT see an "Edit" button
    
    # Check direct edit URL access
    When I try to access "/evaluation/edit.php?id={evaluation_id}"
    Then I should be denied access or redirected

  Scenario: Verify HR Admin can review but not resubmit
    Given an evaluation exists with status "submitted"
    And I am logged in as "admin" (HR Admin)
    
    When I view the evaluation
    Then I should see a "Review" button
    When I click "Review"
    Then I should access the evaluation form
    But I should NOT see a "Submit" button
    And I should see appropriate review/approval options
    
  Scenario: Test database state persistence
    Given I am logged in as "manager1"
    And an evaluation exists with status "draft"
    
    When I submit the evaluation
    Then the database should show:
      | field  | value     |
      | status | submitted |
    
    When I check from a different user session
    Then the database should still show:
      | field  | value     |
      | status | submitted |
    
    And the authorization functions should return:
      | function           | manager_result | hr_admin_result |
      | canEditEvaluation  | false         | true            |
      | canViewEvaluation  | true          | true            |

  Scenario: Edge case - Direct URL manipulation
    Given an evaluation exists with status "submitted"
    
    # Test as Manager
    Given I am logged in as "manager1"
    When I try to access "/evaluation/edit.php?id={evaluation_id}" directly
    Then I should be denied access
    
    # Test as Employee
    Given I am logged in as "emp1"
    When I try to access "/evaluation/edit.php?id={evaluation_id}" directly
    Then I should be denied access
    
    # Test as HR Admin (should work)
    Given I am logged in as "admin"
    When I try to access "/evaluation/edit.php?id={evaluation_id}" directly
    Then I should be granted access for review