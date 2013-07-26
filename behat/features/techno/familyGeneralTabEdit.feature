@dbFull
Feature: Family general tab edit feature

  Background:
    Given I am logged in

  @javascript
  Scenario: Family edit general data scenario, correct input
    Given I am on "techno/family/edit/id/4"
    And I wait for the page to finish loading
    Then I should see "Famille test de processus"
    When I open tab "Général"
  # Vérification du contenu des différents champs du formulaire "Général"
    And the "Libellé" field should contain "Famille test de processus"
    And the "Identifiant" field should contain "famille_test_processus"
    And the "Unité" field should contain "t"
  # Modifications
    When I fill in "Libellé" with "Famille test de processus modifiée"
    And I fill in "Identifiant" with "famille_test_processus_modifiee"
    And I fill in "Unité" with "kg"
    And I click "Enregistrer"
    Then the following message is shown and closed: "Modification effectuée."
    And the "Libellé" field should contain "Famille test de processus"
    And the "Identifiant" field should contain "famille_test_processus_modifiee"
    And the "Unité" field should contain "kg"

  @javascript
  Scenario: Family edit general data scenario, incorrect input
    Given I am on "techno/family/edit/id/4"
    And I wait for the page to finish loading
    When I open tab "Général"
  # Libellé et identifiant et unité vides
    And I fill in "Libellé" with ""
    And I fill in "Identifiant" with ""
    And I fill in "Unité" with ""
    And I click "Enregistrer"
    Then the field "Libellé" should have  error: "Merci de renseigner ce champ."
    And the field "Identifiant" should have  error: "Merci de renseigner ce champ."
    And the field "Unité" should have  error: "Merci de renseigner ce champ."
  # Libellé non vide, identifiant caractères non autorisés, unité invalide
    When I fill in "Libellé" with "Test"
    And I fill in "Identifiant" with "bépo"
    And I fill in "Unité" with "auie"
    And I click "Enregistrer"
    Then the field "Identifiant" should have  error: "Merci d'utiliser seulement les caractères : \"a..z\", \"0..9\", et \"_\"."
    And the field "Unité" should have  error: "Merci de saisir un identifiant d'unité valide."
  # Libellé non vide, identifiant déjà utilisé, unité invalide
    When I fill in "Identifiant" with "combustion_combustible_unite_masse"
    And I fill in "Unité" with "m2"
    And I click "Enregistrer"
    Then the field "Identifiant" should have  error: "Merci de choisir un autre identifiant, celui-ci est déjà utilisé."
    And the field "Unité" should have  error: "Merci de saisir un identifiant d'unité valide."

