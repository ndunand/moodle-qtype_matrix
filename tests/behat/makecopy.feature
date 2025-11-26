@qtype @qtype_matrix
Feature: Test duplicating a matrix question via makecopy in qbank_editquestion
  As a teacher
  In order to have less work
  I need to be able to duplicate my matrix questions via the question bank

  Background:
    Given the following "users" exist:
      | username |
      | teacher  |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |

  Scenario: Copy a Matrix question via qbank_editquestion and makecopy,
    change the copy and ensure the original does not change
    When I am on the "Course 1" "core_question > course question bank" page logged in as teacher
    And I press "Create a new question ..."
    And I set the field "Matrix/Kprime" to "1"
    And I press "Add"
    And I set the following fields to these values:
      | Question name | matrix-001                                        |
      | Question text | Default K-Prime question                          |
      | ID number     | 123                                               |
      | id_shuffleanswers | 1                                        |
      | rows_shorttext[0] | one                                           |
      | rows_shorttext[1] | two                                           |
      | rows_shorttext[2] | three                                           |
      | rows_shorttext[3] | four                                           |
      | cols_shorttext[0] | col1                                           |
      | cols_shorttext[1] | col2                                           |
    And I press "id_submitbutton"
    Then I should see "matrix-001"
    And I choose "Duplicate" action for "matrix-001" in the question bank
    And I set the following fields to these values:
      | Question name | matrix-002                                        |
      | Question text | Another K-Prime question                          |
      | ID number     | 123-Nr2                                           |
      | id_shuffleanswers | 0                                        |
      | rows_shorttext[0] | five                                           |
      | rows_shorttext[1] | six                                           |
      | rows_shorttext[2] | seven                                           |
      | rows_shorttext[3] | eight                                           |
      | cols_shorttext[0] | col3                                           |
      | cols_shorttext[1] | col4                                           |
    And I press "id_submitbutton"
    Then I should see "matrix-001"
    And I should see "matrix-002"
    When I am on the "matrix-001" "core_question > edit" page logged in as teacher
    Then the field "Question name" matches value "matrix-001"
    And the field "Question text" matches value "Default K-Prime question"
    And the field "ID number" matches value "123"
    And the field "id_shuffleanswers" matches value "1"
    And the field "rows_shorttext[0]" matches value "one"
    And the field "rows_shorttext[1]" matches value "two"
    And the field "rows_shorttext[2]" matches value "three"
    And the field "rows_shorttext[3]" matches value "four"
    And the field "cols_shorttext[0]" matches value "col1"
    And the field "cols_shorttext[1]" matches value "col2"
