@dbOneOrganizationWithAxes
Feature: Control

  Background:
    Given I am logged in

  @javascript
  Scenario: Control1
  # Accès à l'onglet "Contrôle"
    Given I am on "orga/cell/details/idCell/1"
    And I open tab "Organisation"
    And I open tab "Contrôle"
    Then I should see the "consistency" datagrid
    And the row 1 of the "consistency" datagrid should contain:
      | control           | diagnostic  | failure |
      | Axe sans membre   | NOT OK        | Année  |
    And the row 2 of the "consistency" datagrid should contain:
      | control                                      | diagnostic  | failure |
      | Membre pour lequel manque un membre parent   | OK        |   |
    And the row 3 of the "consistency" datagrid should contain:
      | control                                      | diagnostic  | failure |
      | Membre sans enfant d'un axe non situé à la racine  | OK        |   |