@qtype @qtype_matrix
Feature: Managing matrix question versions via the form should work
  As a teacher
  I want to be able to create new versions of a question without destroying old versions

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

  Scenario: Create a first version, edit it and save without changes. Currently a v2 is created anyway.
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
    And I should see "matrix-001"
    And I should see "v1" in the table row containing "matrix-001"
    And I am on the "matrix-001" "core_question > edit" page
    And I press "id_submitbutton"
    And I should see "matrix-001"
    And I should see "v2" in the table row containing "matrix-001"

  Scenario: Create three versions, each with different values. Delete v2. Every version should be unaffected.
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
    And I should see "matrix-001"
    And I should see "v1" in the table row containing "matrix-001"
    And I am on the "matrix-001" "core_question > edit" page
    And I set the following fields to these values:
      | Question name | matrix-002                                        |
      | Question text | Default K-Prime question v2                       |
      | ID number     | 123 v2                                            |
      | id_shuffleanswers | 1                                        |
      | rows_shorttext[0] | onev2                                           |
      | rows_shorttext[1] | twov2                                           |
      | rows_shorttext[2] | threev2                                           |
      | rows_shorttext[3] | fourv2                                           |
      | cols_shorttext[0] | col1v2                                           |
      | cols_shorttext[1] | col2v2                                           |
    And I press "id_submitbutton"
    And I should see "matrix-002"
    And I should see "v2" in the table row containing "matrix-002"
    And I am on the "matrix-002" "core_question > edit" page
    And I set the following fields to these values:
      | Question name | matrix-003                                        |
      | Question text | Default K-Prime question v3                       |
      | ID number     | 123 v3                                            |
      | id_shuffleanswers | 1                                        |
      | rows_shorttext[0] | onev3                                           |
      | rows_shorttext[1] | twov3                                           |
      | rows_shorttext[2] | threev3                                           |
      | rows_shorttext[3] | fourv3                                           |
      | cols_shorttext[0] | col1v3                                           |
      | cols_shorttext[1] | col2v3                                           |
    And I press "id_submitbutton"
    And I should see "matrix-003"
    And I should see "v3" in the table row containing "matrix-003"
    And I choose "History" action for "matrix-003" in the question bank
    And I am on the "matrix-001" "core_question > edit" page
    And the field "Question name" matches value "matrix-001"
    And the field "Question text" matches value "Default K-Prime question"
    And the field "ID number" matches value "123"
    And the field "id_shuffleanswers" matches value "1"
    And the field "rows_shorttext[0]" matches value "one"
    And the field "rows_shorttext[1]" matches value "two"
    And the field "rows_shorttext[2]" matches value "three"
    And the field "rows_shorttext[3]" matches value "four"
    And the field "cols_shorttext[0]" matches value "col1"
    And the field "cols_shorttext[1]" matches value "col2"
    And I am on the "matrix-002" "core_question > edit" page
    And the field "Question name" matches value "matrix-002"
    And the field "Question text" matches value "Default K-Prime question v2"
    And the field "ID number" matches value "123 v2"
    And the field "id_shuffleanswers" matches value "1"
    And the field "rows_shorttext[0]" matches value "onev2"
    And the field "rows_shorttext[1]" matches value "twov2"
    And the field "rows_shorttext[2]" matches value "threev2"
    And the field "rows_shorttext[3]" matches value "fourv2"
    And the field "cols_shorttext[0]" matches value "col1v2"
    And the field "cols_shorttext[1]" matches value "col2v2"
    And I am on the "matrix-003" "core_question > edit" page
    And the field "Question name" matches value "matrix-003"
    And the field "Question text" matches value "Default K-Prime question v3"
    And the field "ID number" matches value "123 v3"
    And the field "id_shuffleanswers" matches value "1"
    And the field "rows_shorttext[0]" matches value "onev3"
    And the field "rows_shorttext[1]" matches value "twov3"
    And the field "rows_shorttext[2]" matches value "threev3"
    And the field "rows_shorttext[3]" matches value "fourv3"
    And the field "cols_shorttext[0]" matches value "col1v3"
    And the field "cols_shorttext[1]" matches value "col2v3"
    And I am on the "Course 1" "core_question > course question bank" page
    And I choose "History" action for "matrix-003" in the question bank
    And I choose "Delete" action for "matrix-002" in the question bank
    And I press "Delete"
    And I am on the "matrix-001" "core_question > edit" page
    And the field "Question name" matches value "matrix-001"
    And the field "Question text" matches value "Default K-Prime question"
    And the field "ID number" matches value "123"
    And the field "id_shuffleanswers" matches value "1"
    And the field "rows_shorttext[0]" matches value "one"
    And the field "rows_shorttext[1]" matches value "two"
    And the field "rows_shorttext[2]" matches value "three"
    And the field "rows_shorttext[3]" matches value "four"
    And the field "cols_shorttext[0]" matches value "col1"
    And the field "cols_shorttext[1]" matches value "col2"
    And I am on the "matrix-003" "core_question > edit" page
    And the field "Question name" matches value "matrix-003"
    And the field "Question text" matches value "Default K-Prime question v3"
    And the field "ID number" matches value "123 v3"
    And the field "id_shuffleanswers" matches value "1"
    And the field "rows_shorttext[0]" matches value "onev3"
    And the field "rows_shorttext[1]" matches value "twov3"
    And the field "rows_shorttext[2]" matches value "threev3"
    And the field "rows_shorttext[3]" matches value "fourv3"
    And the field "cols_shorttext[0]" matches value "col1v3"
    And the field "cols_shorttext[1]" matches value "col2v3"
