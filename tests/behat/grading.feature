@qtype @qtype_matrix @javascript
Feature: Grading and regrading using new versions of matrix questions should work
  As a teacher
  I want to be able to regrade my matrix question quiz after I correct my wrong question

  Background:
    Given the following "users" exist:
      | username |
      | teacher  |
      | student  |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
      | student | C1     | student |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "activities" exist:
      | activity | name   | intro              | course | idnumber | grade | navmethod  |
      | quiz     | Quiz 1 | Quiz 1 description | C1     | quiz1    | 100   | free       |

  Scenario: Create a first question version, add it to quiz, attempt the quiz as a student, create second version then regrade.
    When I log in as "teacher"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Matrix/Kprime" question to the "Quiz 1" quiz with:
      | Question name | matrix-001                                        |
      | Question text | Default K-Prime question                          |
      | ID number     | 123                                               |
      | id_shuffleanswers | 1                                        |
      | id_multiple | 0                                        |
      | rows_shorttext[0] | one                                           |
      | rows_shorttext[1] | two                                           |
      | rows_shorttext[2] | three                                           |
      | rows_shorttext[3] | four                                           |
      | cols_shorttext[0] | col1                                           |
      | cols_shorttext[1] | col2                                           |
    And I log out

    And I am on the "Quiz 1" "quiz activity" page logged in as "student"
    And I press "Attempt quiz"
    Then I should see "Default K-Prime question"
    And I set the field with xpath "//td[@class='cell row0col0']//input[@type='radio']" to "0"
    And I press "Finish attempt ..."
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Submit all your answers and finish?" "dialogue"
#    And I should see "BOOM"
    And I should see "Mark 0.00 out of"
    And I log out

    And I am on the "Quiz 1" "quiz activity" page logged in as "teacher"
    And I should see "Attempts: 1"
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
    And I am on the "Quiz 1" "quiz activity" page
    And I navigate to "Results" in current page administration
    And I should see "Attempts: 1"
    And I press "Regrade attempts..."
    And I click on "Regrade now" "button" in the "Regrade" "dialogue"
    And I should see "Regrade completed"
    And I press "Continue"
    And I log out

    And I am on the "Quiz 1" "quiz activity" page logged in as "student"
    And I click on "Review" "link"
    And I should see "Default K-Prime question v2"
    And I should see "Mark 0.00 out of"
