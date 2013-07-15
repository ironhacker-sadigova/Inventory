@dbFull
Feature: Organization input tab feature

  Background:
    Given I am logged in

  @javascript
  Scenario: Filter on organization members
  # Accès à l'onglet "Inventaires"
    Given I am on "orga/cell/details/idCell/1"
    And I open tab "Saisies"
    Then I should see the "aFGranularity1Input8" datagrid
    And the "aFGranularity1Input8" datagrid should contain 3 row
  # Filtre sur le site "Annecy"
    When I open collapse "Filtres"
    And I select "Annecy" from "aFGranularity1Input8_site_filterForm"
    And I click "Filtrer"
    Then the "aFGranularity1Input8" datagrid should contain 1 row
  # Bouton "Réinitialiser"
    When I click "Réinitialiser"
    Then the "aFGranularity1Input8" datagrid should contain 3 row
