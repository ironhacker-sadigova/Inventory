@dbEmpty
Feature: AF Category feature

  Background:
    Given I am logged in

  @javascript
  Scenario: Creation of a category
    Given I am on "af/af/tree"
  # Ajout d'une catégorie, libellé vide
    When I click "Ajouter une catégorie"
    Then I should see the popup "Ajout d'une catégorie"
    When I click "Valider"
    Then the field "label" should have error: "Merci de renseigner ce champ."
  # Ajout d'une catégorie, libellé non vide
    When I fill in "label" with "Test"
    And I click "Valider"
    Then the following message is shown and closed: "Ajout effectué."

  @javascript
  Scenario: Edition of a category
    Given I am on "af/af/tree"
  # Modification du libellé
    When I click "Catégorie vide"
    Then I should see the popup "Édition d'une catégorie"
    When I fill in "afTree_labelEdit" with "Catégorie vide modifiée"
    And I click "Confirmer"
    Then the following message is shown and closed: "Modification effectuée."
    And I should see "Catégorie vide modifiée"
  # Déplacement dans une autre catégorie
    When I click "Catégorie vide modifiée"
    Then I should see the popup "Édition d'une catégorie"
    When I select "Catégorie contenant une sous-catégorie" from "afTree_changeParent"
    And I click "Confirmer"
    Then the following message is shown and closed: "Modification effectuée."
  # Déplacement à la racine
    When I wait 3 seconds
    And I click "Catégorie vide modifiée"
    And I select "Aucun" from "afTree_changeParent"
    And I click "Confirmer"
    Then the following message is shown and closed: "Modification effectuée."
  # Déplacement en premier
    When I click "Catégorie vide modifiée"
    And I check "Premier"
    And I click "Confirmer"
    Then the following message is shown and closed: "Modification effectuée."
  # Déplacement après une autre catégorie
    When I click "Catégorie vide modifiée"
    And I check "Après"
    And I select "Catégorie 2" from "afTree_selectAfter"
    And I click "Confirmer"
    Then the following message is shown and closed: "Modification effectuée."
  # Déplacement en dernier
    When I click "Catégorie vide modifiée"
    And I check "Dernier"
    And I click "Confirmer"
    Then the following message is shown and closed: "Modification effectuée."

  @javascript
  Scenario:  Deletion of a category
  # Catégorie vide
    When I click "Catégorie vide"
    Then I should see the popup "Édition d'une catégorie"
    When I click "Supprimer"
    Then I should see the popup "Demande de confirmation"
    And I click "Confirmer"
    Then the following message is shown and closed: "Suppression effectuée."
    And I should not see "Catégorie vide"
  # Catégorie contenant une sous-catégorie
    When I click "Catégorie contenant une sous-catégorie"
    Then I should see the popup "Édition d'une catégorie"
    When I click "Supprimer"
    Then I should see the popup "Demande de confirmation"
    And I click "Confirmer"
    Then the following message is shown and closed: "Suppression effectuée."
    # TODO : interdire la suppression d'une catégorie contenant une autre catégorie
  # Catégorie contenant un formulaire
    When I click "Catégorie contenant un formulaire"
    Then I should see the popup "Édition d'une catégorie"
    When I click "Supprimer"
    Then I should see the popup "Demande de confirmation"
    And I click "Confirmer"
    Then the following message is shown and closed: "Suppression effectuée."








