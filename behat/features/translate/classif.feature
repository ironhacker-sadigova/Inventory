@dbFull
Feature: Edit Classification translations feature

  Background:
    Given I am logged in

  @javascript
  Scenario: Edit classification axes translations scenario
  # Affichage du datagrid
    Given I am on "classification/translate?library=1"
    And I wait for the page to finish loading
    Then I should see the "datagridTranslate_Classification_Domain_Axis_label" datagrid
    And the row 1 of the "datagridTranslate_Classification_Domain_Axis_label" datagrid should contain:
      | identifier | fr  |
      | gaz        | Gaz |
  # Édition traductions
    When I set "Gaz (français)" for column "fr" of row 1 of the "datagridTranslate_Classification_Domain_Axis_label" datagrid
    And I set "Gas (anglais)" for column "en" of row 1 of the "datagridTranslate_Classification_Domain_Axis_label" datagrid
    Then the row 1 of the "datagridTranslate_Classification_Domain_Axis_label" datagrid should contain:
      | identifier | fr             | en            |
      | gaz        | Gaz (français) | Gas (anglais) |


  @javascript
  Scenario: Edit classification members translations scenario
  # Affichage du datagrid
    Given I am on "classification/translate?library=1"
    And I wait for the page to finish loading
    Then I should see the "datagridTranslate_Classification_Domain_Member_label" datagrid
    And the row 1 of the "datagridTranslate_Classification_Domain_Member_label" datagrid should contain:
      | identifier | fr  |
      | gaz \| co2 | CO2 |
  # Édition traductions
    When I set "CO2 (français)" for column "fr" of row 1 of the "datagridTranslate_Classification_Domain_Member_label" datagrid
    And I set "CO2 (anglais)" for column "en" of row 1 of the "datagridTranslate_Classification_Domain_Member_label" datagrid
    Then the row 1 of the "datagridTranslate_Classification_Domain_Member_label" datagrid should contain:
      | identifier | fr             | en            |
      | gaz \| co2 | CO2 (français) | CO2 (anglais) |

  @javascript
  Scenario: Edit classification indicators translations scenario
  # Affichage du datagrid
    Given I am on "classification/translate?library=1"
    And I wait for the page to finish loading
    Then I should see the "datagridTranslate_Classification_Domain_Indicator_label" datagrid
    And the row 1 of the "datagridTranslate_Classification_Domain_Indicator_label" datagrid should contain:
      | identifier | fr  |
      | ges        | GES |
  # Édition traductions
    When I set "GES (français)" for column "fr" of row 1 of the "datagridTranslate_Classification_Domain_Indicator_label" datagrid
    And I set "GES (anglais)" for column "en" of row 1 of the "datagridTranslate_Classification_Domain_Indicator_label" datagrid
    Then the row 1 of the "datagridTranslate_Classification_Domain_Indicator_label" datagrid should contain:
      | identifier | fr             | en            |
      | ges        | GES (français) | GES (anglais) |

  @javascript
  Scenario: Edit classification contexts translations scenario
  # Affichage du datagrid
    Given I am on "classification/translate?library=1"
    And I wait for the page to finish loading
    Then I should see the "datagridTranslate_Classification_Domain_Context_label" datagrid
    And the row 1 of the "datagridTranslate_Classification_Domain_Context_label" datagrid should contain:
      | identifier | fr      |
      | general    | Général |
  # Édition traductions
    When I set "Général (français)" for column "fr" of row 1 of the "datagridTranslate_Classification_Domain_Context_label" datagrid
    And I set "General (anglais)" for column "en" of row 1 of the "datagridTranslate_Classification_Domain_Context_label" datagrid
    Then the row 1 of the "datagridTranslate_Classification_Domain_Context_label" datagrid should contain:
      | identifier | fr                 | en                |
      | general    | Général (français) | General (anglais) |
