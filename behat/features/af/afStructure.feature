@dbFull
Feature: AF structure feature

  Background:
    Given I am logged in

  @javascript
  Scenario: Change the position and parent of an AF group
    Given I am on "af/edit/menu/id/4"
    And I wait for the page to finish loading
    And I open tab "Structure"
  # Déplacement d'un groupe, à la fin
    When I click "Groupe vide"
    Then I should see the popup "Déplacement dans la structure du formulaire"
    When I check "Dernier"
    And I click "Confirmer"
    Then the following message is shown and closed: "Modification effectuée."
  # Déplacement d'un groupe, au début
    When I click "Groupe contenant un sous-groupe"
    Then I should see the popup "Déplacement dans la structure du formulaire"
    When I check "Premier"
    And I click "Confirmer"
    Then the following message is shown and closed: "Modification effectuée."
  # Déplacement d'un groupe, après un autre composant ou group
    When I wait 5 seconds
    And I click "Groupe contenant un champ"
    Then I should see the popup "Déplacement dans la structure du formulaire"
    When I check "Après"
    And I select "Champ sélection simple" from "afTree_selectAfter"
    And I click "Confirmer"
    And I wait 5 seconds
    Then the following message is shown and closed: "Modification effectuée."
  # Modification du parent d'un groupe (depuis la racine)
    When I click "Groupe vide"
    Then I should see the popup "Déplacement dans la structure du formulaire"
    When I select "Groupe contenant un sous-groupe" from "afTree_changeParent"
    And I click "Confirmer"
    Then the following message is shown and closed: "Modification effectuée."
  # Modification du parent d'un groupe (vers la racine)
    When I click "Groupe vide"
    Then I should see the popup "Déplacement dans la structure du formulaire"
    When I select "Racine" from "afTree_changeParent"
    And I click "Confirmer"
    Then the following message is shown and closed: "Modification effectuée."

  @javascript
  Scenario: Change the position and parent of an AF component (not a group)
    Given I am on "af/edit/menu/id/4"
    And I wait for the page to finish loading
    And I open tab "Structure"
  # Déplacement d'un composant, à la fin
    When I click "Champ sélection simple"
    Then I should see the popup "Déplacement dans la structure du formulaire"
    When I check "Dernier"
    And I click "Confirmer"
    Then the following message is shown and closed: "Modification effectuée."
  # Déplacement d'un composant, au début
    When I click "	Champ booléen"
    Then I should see the popup "Déplacement dans la structure du formulaire"
    When I check "Premier"
    And I click "Confirmer"
    Then the following message is shown and closed: "Modification effectuée."
  # Déplacement d'un composant, après un autre composant
    When I click "Champ sélection simple"
    Then I should see the popup "Déplacement dans la structure du formulaire"
    When I check "Après"
    And I select "Groupe contenant un sous-groupe" from "afTree_selectAfter"
    And I click "Confirmer"
    Then the following message is shown and closed: "Modification effectuée."
  # Modification du parent d'un composant (depuis la racine)
    When I click "Champ sélection multiple"
    Then I should see the popup "Déplacement dans la structure du formulaire"
    When I select "Groupe contenant un champ" from "afTree_changeParent"
    And I click "Confirmer"
    Then the following message is shown and closed: "Modification effectuée."
  # Modification du parent d'un composant (vers la racine)
    When I click "Champ sélection multiple"
    Then I should see the popup "Déplacement dans la structure du formulaire"
    When I select "Racine" from "afTree_changeParent"
    And I click "Confirmer"
    Then the following message is shown and closed: "Modification effectuée."